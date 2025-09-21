<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shared media asset that can be attached to multiple products.
 */
#[ORM\Entity]
#[ORM\Table(name: 'media_assets')]
#[ORM\HasLifecycleCallbacks]
final class MediaAsset extends BaseEntity implements ValidatableInterface
{
    use ValidatableTrait;

    #[ORM\Column(type: Types::STRING, length: 512, unique: true)]
    #[Assert\NotBlank(message: 'Media asset URL is required')]
    #[Assert\Url(message: 'Media asset URL must be a valid URL')]
    private string $url;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $altText = null;

    public function __construct(string $url, ?string $altText = null)
    {
        $this->url = $url;
        $this->altText = $altText !== '' ? $altText : null;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): static
    {
        $this->altText = $altText !== '' ? $altText : null;

        return $this;
    }
}
