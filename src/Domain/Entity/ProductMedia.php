<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Association between a product and a media asset.
 */
#[ORM\Entity]
#[ORM\Table(name: 'product_media')]
#[ORM\UniqueConstraint(name: 'product_media_unique_asset', columns: ['product_id', 'media_asset_id'])]
#[ORM\HasLifecycleCallbacks]
final class ProductMedia extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'media')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: MediaAsset::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MediaAsset $mediaAsset = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isPrimary = false;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position = 0;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $altTextOverride = null;

    public function __construct(Product $product, MediaAsset $mediaAsset)
    {
        $this->product = $product;
        $this->mediaAsset = $mediaAsset;
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

    public function getMediaAsset(): ?MediaAsset
    {
        return $this->mediaAsset;
    }

    public function setMediaAsset(MediaAsset $mediaAsset): static
    {
        $this->mediaAsset = $mediaAsset;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function markAsPrimary(bool $primary = true): static
    {
        $this->isPrimary = $primary;

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

    public function getAltTextOverride(): ?string
    {
        return $this->altTextOverride;
    }

    public function setAltTextOverride(?string $altTextOverride): static
    {
        $this->altTextOverride = $altTextOverride !== '' ? $altTextOverride : null;

        return $this;
    }

    public function getEffectiveAltText(): ?string
    {
        if ($this->altTextOverride !== null && $this->altTextOverride !== '') {
            return $this->altTextOverride;
        }

        return $this->mediaAsset?->getAltText();
    }
}
