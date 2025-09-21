<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Infrastructure\Doctrine\Type\MoneyType;
use App\Infrastructure\Doctrine\Type\ProductSkuType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Sellable variant attached to a parent product.
 */
#[ORM\Entity]
#[ORM\Table(name: 'product_variants')]
#[ORM\UniqueConstraint(name: 'product_variant_unique_sku', columns: ['sku'])]
#[ORM\HasLifecycleCallbacks]
final class ProductVariant extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(type: ProductSkuType::NAME, length: 120, unique: true)]
    private ProductSku $sku;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: MoneyType::NAME, nullable: true)]
    private ?Money $price = null;

    #[ORM\Column(type: MoneyType::NAME, nullable: true)]
    private ?Money $comparePrice = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDefault = false;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position = 0;

    /** @var Collection<int, ProductVariantAttribute> */
    #[ORM\OneToMany(mappedBy: 'variant', targetEntity: ProductVariantAttribute::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $attributes;

    public function __construct(Product $product, ProductSku|string $sku, ?string $name = null)
    {
        $this->product = $product;
        $this->sku = $sku instanceof ProductSku ? $sku : new ProductSku($sku);
        $this->name = $name !== null && $name !== '' ? $name : $product->getName();
        $this->attributes = new ArrayCollection();
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getSku(): ProductSku
    {
        return $this->sku;
    }

    public function setSku(ProductSku|string $sku): static
    {
        $this->sku = $sku instanceof ProductSku ? $sku : new ProductSku($sku);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name !== null && $name !== '' ? $name : $this->product?->getName() ?? '';

        return $this;
    }

    public function getPrice(): ?Money
    {
        return $this->price;
    }

    public function setPrice(Money|string|null $price, ?string $currency = null): static
    {
        if ($price === null) {
            $this->price = null;

            return $this;
        }

        if ($price instanceof Money) {
            $this->price = $price;

            return $this;
        }

        $this->price = new Money($price, $currency ?? $this->product?->getPrice()->getCurrency() ?? Money::DEFAULT_CURRENCY);

        return $this;
    }

    public function getComparePrice(): ?Money
    {
        return $this->comparePrice;
    }

    public function setComparePrice(Money|string|null $comparePrice, ?string $currency = null): static
    {
        if ($comparePrice === null) {
            $this->comparePrice = null;

            return $this;
        }

        if ($comparePrice instanceof Money) {
            $this->comparePrice = $comparePrice;

            return $this;
        }

        $this->comparePrice = new Money($comparePrice, $currency ?? $this->product?->getPrice()->getCurrency() ?? Money::DEFAULT_CURRENCY);

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function markAsDefault(bool $default = true): static
    {
        $this->isDefault = $default;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = max(0, $position);

        return $this;
    }

    /**
     * @return Collection<int, ProductVariantAttribute>
     */
    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function clearAttributes(): static
    {
        $this->attributes->clear();

        return $this;
    }

    /**
     * @param array<string, string|null> $attributes
     * @return $this
     */
    public function replaceAttributes(array $attributes): self
    {
        $this->attributes->clear();
        foreach ($attributes as $name => $value) {
            if ($name === '' || null === $value) {
                continue;
            }

            $this->attributes->add(new ProductVariantAttribute($this, $name, $value));
        }

        return $this;
    }

    /**
     * @return array<string, string|null>
     */
    public function getAttributeMap(): array
    {
        $map = [];
        foreach ($this->attributes as $attribute) {
            $map[$attribute->getName()] = $attribute->getValue();
        }

        return $map;
    }
}
