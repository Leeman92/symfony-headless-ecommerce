<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Doctrine\DBAL\Types\Types;
/*
 * Structured SEO metadata for a product.
 */
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class ProductSeo
{
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $keywords = null;

    public function __construct(?string $title = null, ?string $description = null, ?string $keywords = null)
    {
        $this->title = $title !== '' ? $title : null;
        $this->description = $description !== '' ? $description : null;
        $this->keywords = $keywords !== '' ? $keywords : null;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function withTitle(?string $title): self
    {
        return new self($title, $this->description, $this->keywords);
    }

    public function withDescription(?string $description): self
    {
        return new self($this->title, $description, $this->keywords);
    }

    public function withKeywords(?string $keywords): self
    {
        return new self($this->title, $this->description, $keywords);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function isEmpty(): bool
    {
        return $this->title === null && $this->description === null && $this->keywords === null;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->keywords !== null) {
            $data['keywords'] = $this->keywords;
        }

        return $data;
    }
}
