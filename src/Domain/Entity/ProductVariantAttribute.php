<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Name/value pair describing a variant option.
 */
#[ORM\Entity]
#[ORM\Table(name: 'product_variant_attributes')]
#[ORM\UniqueConstraint(name: 'variant_attribute_unique_name', columns: ['variant_id', 'name'])]
final class ProductVariantAttribute extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: ProductVariant::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ProductVariant $variant = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $value;

    public function __construct(ProductVariant $variant, string $name, ?string $value)
    {
        $this->variant = $variant;
        $this->name = $name;
        $this->value = $value;
    }

    public function getVariant(): ?ProductVariant
    {
        return $this->variant;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
