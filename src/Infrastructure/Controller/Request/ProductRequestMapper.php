<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Request;

use App\Domain\Entity\Category;
use App\Domain\Entity\MediaAsset;
use App\Domain\Entity\Product;
use App\Domain\Entity\ProductMedia;
use App\Domain\Entity\ProductVariant;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Domain\ValueObject\Slug;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use function array_key_exists;
use function filter_var;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
use function is_string;
use function sprintf;
use function str_replace;
use function strtoupper;
use function trim;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOL;

final class ProductRequestMapper
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createProduct(array $payload): Product
    {
        $name = $this->extractString($payload, 'name');
        $slugValue = $payload['slug'] ?? null;
        $slug = $slugValue === null || $slugValue === ''
            ? Slug::fromString($name)
            : new Slug((string) $slugValue);

        $price = $this->parseMoney($payload['price'] ?? null, $payload['currency'] ?? null);
        $category = $this->resolveCategory($payload, true);
        if (!$category instanceof Category) {
            throw new InvalidArgumentException('Category is required');
        }

        $product = new Product($name, $slug, $price, $category);

        $this->apply($product, $payload, true);

        return $product;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function apply(Product $product, array $payload, bool $isCreate = false): Product
    {
        if (array_key_exists('name', $payload) && !$isCreate) {
            $product->setName($this->extractString($payload, 'name'));
        }

        if (array_key_exists('slug', $payload) && !$isCreate) {
            $slugValue = $payload['slug'];
            if ($slugValue === null || $slugValue === '') {
                throw new InvalidArgumentException('Slug cannot be empty');
            }
            $product->setSlug(new Slug((string) $slugValue));
        }

        if (array_key_exists('description', $payload)) {
            $product->setDescription($this->extractNullableString($payload, 'description'));
        }

        if (array_key_exists('short_description', $payload)) {
            $product->setShortDescription($this->extractNullableString($payload, 'short_description'));
        }

        if (array_key_exists('price', $payload) && !$isCreate) {
            $product->setPrice($this->parseMoney($payload['price'], $payload['currency'] ?? $product->getPrice()->getCurrency()));
        }

        if (array_key_exists('compare_price', $payload)) {
            $compare = $payload['compare_price'];
            if ($compare === null) {
                $product->setComparePrice(null);
            } else {
                $product->setComparePrice($this->parseMoney($compare, $product->getPrice()->getCurrency()));
            }
        }

        if (array_key_exists('stock', $payload)) {
            $product->setStock($this->extractInt($payload, 'stock', allowZero: true));
        }

        if (array_key_exists('track_stock', $payload)) {
            $product->setTrackStock($this->extractBool($payload, 'track_stock'));
        }

        if (array_key_exists('is_active', $payload)) {
            $product->setIsActive($this->extractBool($payload, 'is_active'));
        }

        if (array_key_exists('is_featured', $payload)) {
            $product->setIsFeatured($this->extractBool($payload, 'is_featured'));
        }

        if (array_key_exists('low_stock_threshold', $payload)) {
            $threshold = $payload['low_stock_threshold'];
            if ($threshold === null || $threshold === '') {
                $product->setLowStockThreshold(null);
            } else {
                $product->setLowStockThreshold($this->extractInt($payload, 'low_stock_threshold'));
            }
        }

        if (array_key_exists('sku', $payload)) {
            $sku = $payload['sku'];
            if ($sku === null || $sku === '') {
                $product->setSku(null);
            } else {
                $product->setSku(new ProductSku((string) $sku));
            }
        }

        if (array_key_exists('attributes', $payload)) {
            $product->setAttributes($this->extractNullableArray($payload, 'attributes'));
        }

        if (array_key_exists('variants', $payload)) {
            $this->applyVariantsPayload($product, $payload['variants']);
        }

        if (array_key_exists('images', $payload)) {
            $this->applyImagesPayload($product, $payload['images']);
        }

        if (array_key_exists('seo', $payload)) {
            $this->applySeoPayload($product, $payload['seo']);
        }

        $category = $this->resolveCategory($payload, false);
        if ($category instanceof Category) {
            $product->setCategory($category);
        }

        return $product;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveCategory(array $payload, bool $required): ?Category
    {
        $categoryData = $payload['category'] ?? null;
        $categoryId = $payload['category_id'] ?? ($categoryData['id'] ?? null);
        $categorySlug = $payload['category_slug'] ?? ($categoryData['slug'] ?? null);

        $repository = $this->entityManager->getRepository(Category::class);

        if ($categoryId !== null) {
            $id = (int) $categoryId;
            $category = $repository->find($id);
            if (!$category instanceof Category) {
                throw new InvalidArgumentException(sprintf('Category with ID %d not found', $id));
            }

            return $category;
        }

        if ($categorySlug !== null) {
            $slug = new Slug((string) $categorySlug);
            $category = $repository->findOneBy(['slug' => $slug]);
            if (!$category instanceof Category) {
                throw new InvalidArgumentException(sprintf('Category with slug %s not found', $slug->getValue()));
            }

            return $category;
        }

        if ($required) {
            throw new InvalidArgumentException('Category is required');
        }

        return null;
    }

    private function parseMoney(mixed $value, ?string $currencyHint = null): Money
    {
        if ($value instanceof Money) {
            return $value;
        }

        $currency = $currencyHint !== null && $currencyHint !== '' ? strtoupper($currencyHint) : null;

        if (is_array($value)) {
            if (!isset($value['amount'])) {
                throw new InvalidArgumentException('Price must include an amount');
            }
            $amount = (string) $value['amount'];
            $currency = isset($value['currency']) && $value['currency'] !== ''
                ? strtoupper((string) $value['currency'])
                : ($currency ?? Money::DEFAULT_CURRENCY);

            return new Money($amount, $currency);
        }

        if (is_string($value) || is_numeric($value)) {
            $amount = (string) $value;

            return new Money($amount, $currency ?? Money::DEFAULT_CURRENCY);
        }

        throw new InvalidArgumentException('Price must be a string or array with amount');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractString(array $payload, string $key): string
    {
        if (!isset($payload[$key]) || trim((string) $payload[$key]) === '') {
            throw new InvalidArgumentException(sprintf('%s is required', ucfirst(str_replace('_', ' ', $key))));
        }

        return trim((string) $payload[$key]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractNullableString(array $payload, string $key): ?string
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        $value = $payload[$key];
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractInt(array $payload, string $key, bool $allowZero = false): int
    {
        if (!array_key_exists($key, $payload)) {
            throw new InvalidArgumentException(sprintf('%s is required', ucfirst(str_replace('_', ' ', $key))));
        }

        $value = $payload[$key];
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('%s must be numeric', ucfirst(str_replace('_', ' ', $key))));
        }

        $intValue = (int) $value;
        if ($intValue < 0 || (!$allowZero && $intValue === 0)) {
            throw new InvalidArgumentException(sprintf('%s must be positive', ucfirst(str_replace('_', ' ', $key))));
        }

        return $intValue;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractBool(array $payload, string $key): bool
    {
        if (!array_key_exists($key, $payload)) {
            throw new InvalidArgumentException(sprintf('%s is required', ucfirst(str_replace('_', ' ', $key))));
        }

        return filter_var($payload[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? throw new InvalidArgumentException(sprintf('%s must be boolean', ucfirst(str_replace('_', ' ', $key))));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<mixed>|null
     */
    private function extractNullableArray(array $payload, string $key): ?array
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        if (!is_array($payload[$key])) {
            throw new InvalidArgumentException(sprintf('%s must be an array', ucfirst(str_replace('_', ' ', $key))));
        }

        return $payload[$key];
    }

    private function applyVariantsPayload(Product $product, mixed $payload): void
    {
        if ($payload === null) {
            $product->clearVariants();

            return;
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Variants must be provided as an array');
        }

        $existingBySku = [];
        foreach ($product->getVariants() as $existingVariant) {
            $existingBySku[$existingVariant->getSku()->getValue()] = $existingVariant;
        }

        $position = 0;
        $hasDefault = false;

        foreach ($payload as $rawVariant) {
            if (!is_array($rawVariant)) {
                throw new InvalidArgumentException('Each variant entry must be an array');
            }

            $sku = $rawVariant['sku'] ?? null;
            $skuString = $this->toNullableString($sku);
            if ($skuString === null) {
                throw new InvalidArgumentException('Variant sku is required');
            }

            $variant = $existingBySku[$skuString] ?? new ProductVariant($product, new ProductSku($skuString));
            unset($existingBySku[$skuString]);

            if (!$product->getVariants()->contains($variant)) {
                $product->addVariant($variant);
            }

            $variant->setSku(new ProductSku($skuString));
            $variant->setName($this->toNullableString($rawVariant['name'] ?? null));

            if (array_key_exists('price', $rawVariant)) {
                $pricePayload = $rawVariant['price'];
                $variant->setPrice($pricePayload === null ? null : $this->parseMoney($pricePayload, $product->getPrice()->getCurrency()));
            }

            if (array_key_exists('compare_price', $rawVariant)) {
                $comparePayload = $rawVariant['compare_price'];
                $variant->setComparePrice($comparePayload === null ? null : $this->parseMoney($comparePayload, $product->getPrice()->getCurrency()));
            }

            if (array_key_exists('stock', $rawVariant)) {
                $variant->setStock($this->extractVariantStock($rawVariant['stock']));
            }

            $isDefault = $this->extractVariantDefault($rawVariant['is_default'] ?? null);
            $variant->markAsDefault($isDefault);
            if ($isDefault) {
                $hasDefault = true;
            }

            if (array_key_exists('position', $rawVariant)) {
                $variant->setPosition((int) $rawVariant['position']);
            } else {
                $variant->setPosition($position);
            }

            $attributes = $this->extractVariantAttributes($rawVariant);
            $variant->replaceAttributes($attributes);

            ++$position;
        }

        if (!$hasDefault) {
            $firstVariant = $product->getVariants()->first();
            if ($firstVariant instanceof ProductVariant) {
                $firstVariant->markAsDefault(true);
            }
        }

        foreach ($existingBySku as $variantToRemove) {
            $product->removeVariant($variantToRemove);
        }
    }

    private function applyImagesPayload(Product $product, mixed $payload): void
    {
        if ($payload === null) {
            $product->clearMedia();

            return;
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Images must be provided as an array');
        }

        $existingById = [];
        foreach ($product->getMedia() as $media) {
            $id = $media->getId();
            if ($id !== null) {
                $existingById[$id] = $media;
            }
        }

        $position = 0;
        $hasPrimary = false;

        foreach ($payload as $rawImage) {
            if (!is_array($rawImage)) {
                throw new InvalidArgumentException('Each image entry must be an array');
            }

            $media = null;
            if (array_key_exists('id', $rawImage) && $rawImage['id'] !== null) {
                $mediaId = (int) $rawImage['id'];
                $media = $existingById[$mediaId] ?? null;
                unset($existingById[$mediaId]);
            }

            $asset = $this->resolveMediaAsset($rawImage);

            if ($media === null) {
                $media = new ProductMedia($product, $asset);
                $product->addMedia($media);
            } else {
                $media->setMediaAsset($asset);
            }

            $media->setPosition(array_key_exists('position', $rawImage) ? (int) $rawImage['position'] : $position);

            $isPrimary = $this->extractImagePrimary($rawImage['is_primary'] ?? null);
            $media->markAsPrimary($isPrimary);
            if ($isPrimary) {
                foreach ($product->getMedia() as $candidate) {
                    if ($candidate !== $media) {
                        $candidate->markAsPrimary(false);
                    }
                }
            }
            if ($isPrimary) {
                $hasPrimary = true;
            }

            if (array_key_exists('alt_override', $rawImage)) {
                $media->setAltTextOverride($this->toNullableString($rawImage['alt_override']));
            } elseif (array_key_exists('alt', $rawImage)) {
                $asset->setAltText($this->toNullableString($rawImage['alt']));
                $media->setAltTextOverride(null);
            }

            ++$position;
        }

        if (!$hasPrimary) {
            $firstMedia = $product->getMedia()->first();
            if ($firstMedia instanceof ProductMedia) {
                $firstMedia->markAsPrimary(true);
            }
        }

        foreach ($existingById as $mediaToRemove) {
            $product->removeMedia($mediaToRemove);
        }
    }

    private function applySeoPayload(Product $product, mixed $payload): void
    {
        if ($payload === null) {
            $product->setSeoData(null);

            return;
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('SEO data must be provided as an array');
        }

        $seo = [];
        $title = $this->toNullableString($payload['title'] ?? null);
        $description = $this->toNullableString($payload['description'] ?? null);
        $keywords = $this->toNullableString($payload['keywords'] ?? null);

        if ($title !== null) {
            $seo['title'] = $title;
        }
        if ($description !== null) {
            $seo['description'] = $description;
        }
        if ($keywords !== null) {
            $seo['keywords'] = $keywords;
        }

        $product->setSeoData($seo === [] ? null : $seo);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveMediaAsset(array $payload): MediaAsset
    {
        $repository = $this->entityManager->getRepository(MediaAsset::class);

        if (array_key_exists('asset_id', $payload) && $payload['asset_id'] !== null) {
            $assetId = (int) $payload['asset_id'];
            $asset = $repository->find($assetId);
            if (!$asset instanceof MediaAsset) {
                throw new InvalidArgumentException(sprintf('Media asset with ID %d not found', $assetId));
            }

            if (array_key_exists('alt', $payload)) {
                $asset->setAltText($this->toNullableString($payload['alt']));
            }

            return $asset;
        }

        $url = $this->toNullableString($payload['url'] ?? null);
        if ($url === null) {
            throw new InvalidArgumentException('Image url is required when no asset_id is provided');
        }

        $asset = $repository->findOneBy(['url' => $url]);
        if ($asset instanceof MediaAsset) {
            if (array_key_exists('alt', $payload)) {
                $asset->setAltText($this->toNullableString($payload['alt']));
            }

            return $asset;
        }

        $asset = new MediaAsset($url, $this->toNullableString($payload['alt'] ?? null));
        $this->entityManager->persist($asset);

        return $asset;
    }

    private function extractVariantStock(mixed $stock): ?int
    {
        if ($stock === null || $stock === '') {
            return null;
        }

        if (!is_numeric($stock)) {
            throw new InvalidArgumentException('Variant stock must be numeric');
        }

        $intValue = (int) $stock;
        if ($intValue < 0) {
            throw new InvalidArgumentException('Variant stock must be zero or positive');
        }

        return $intValue;
    }

    private function extractVariantDefault(mixed $flag): bool
    {
        if ($flag === null) {
            return false;
        }

        if (is_bool($flag)) {
            return $flag;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string|null>
     */
    private function extractVariantAttributes(array $payload): array
    {
        $attributes = [];

        if (array_key_exists('attributes', $payload) && $payload['attributes'] !== null) {
            if (!is_array($payload['attributes'])) {
                throw new InvalidArgumentException('Variant attributes must be provided as an array');
            }

            foreach ($payload['attributes'] as $name => $value) {
                if (!is_string($name) || $name === '') {
                    continue;
                }

                $attributes[$name] = $this->toNullableString($value);
            }
        }

        $reservedKeys = ['id', 'sku', 'name', 'price', 'compare_price', 'stock', 'is_default', 'position', 'attributes'];

        foreach ($payload as $key => $value) {
            if (in_array($key, $reservedKeys, true)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $attributes[$key] = $this->toNullableString($value);
            }
        }

        return $attributes;
    }

    private function extractImagePrimary(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
