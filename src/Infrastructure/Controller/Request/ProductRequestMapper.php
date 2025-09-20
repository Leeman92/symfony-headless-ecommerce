<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Request;

use App\Domain\Entity\Category;
use App\Domain\Entity\Product;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Domain\ValueObject\Slug;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

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
            $product->setVariants($this->extractNullableArray($payload, 'variants'));
        }

        if (array_key_exists('images', $payload)) {
            $product->setImages($this->extractNullableArray($payload, 'images'));
        }

        if (array_key_exists('seo', $payload)) {
            $product->setSeoData($this->extractNullableArray($payload, 'seo'));
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
                : ($currency ?? 'USD');

            return new Money($amount, $currency);
        }

        if (is_string($value) || is_numeric($value)) {
            $amount = (string) $value;
            return new Money($amount, $currency ?? 'USD');
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
}
