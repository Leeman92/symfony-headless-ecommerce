<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Infrastructure\Doctrine\Type\MoneyType;
use App\Infrastructure\Doctrine\Type\ProductSkuType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Order item entity representing individual products in an order
 * 
 * Stores product information at the time of purchase to maintain
 * historical accuracy even if product details change later.
 */
#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
#[ORM\HasLifecycleCallbacks]
final class OrderItem extends BaseEntity implements ValidatableInterface
{
    use ValidatableTrait;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Order item must belong to an order')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Order item must reference a product')]
    private ?Product $product = null;

    // Store product information at time of purchase for historical accuracy
    #[ORM\Column(type: Types::STRING, length: 200)]
    #[Assert\NotBlank(message: 'Product name is required')]
    #[Assert\Length(max: 200, maxMessage: 'Product name cannot be longer than {{ limit }} characters')]
    private string $productName;

    #[ORM\Column(type: ProductSkuType::NAME, nullable: true)]
    private ?ProductSku $productSku = null;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $unitPrice;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'Quantity is required')]
    #[Assert\Positive(message: 'Quantity must be positive')]
    private int $quantity;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $totalPrice;

    public function __construct(
        Product $product,
        int $quantity,
        Money|string|null $unitPrice = null
    ) {
        $this->product = $product;
        $this->productName = $product->getName();
        $this->productSku = $product->getSku();

        if ($unitPrice === null) {
            $this->unitPrice = $product->getPrice();
        } elseif ($unitPrice instanceof Money) {
            $this->unitPrice = $unitPrice;
        } else {
            $this->unitPrice = new Money($unitPrice, $product->getPrice()->getCurrency());
        }

        $this->quantity = $quantity;
        $this->calculateTotalPrice();
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
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

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getProductSku(): ?ProductSku
    {
        return $this->productSku;
    }

    public function setProductSku(ProductSku|string|null $productSku): static
    {
        if ($productSku === null) {
            $this->productSku = null;
        } elseif ($productSku instanceof ProductSku) {
            $this->productSku = $productSku;
        } else {
            $this->productSku = new ProductSku($productSku);
        }
        return $this;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(Money|string $unitPrice): static
    {
        if ($unitPrice instanceof Money) {
            $this->unitPrice = $unitPrice;
        } else {
            $this->unitPrice = new Money($unitPrice, $this->unitPrice->getCurrency());
        }

        $this->calculateTotalPrice();
        return $this;
    }

    public function getUnitPriceAsFloat(): float
    {
        return $this->unitPrice->getAmountAsFloat();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        $this->calculateTotalPrice();
        return $this;
    }

    public function getTotalPrice(): Money
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(Money|string $totalPrice): static
    {
        if ($totalPrice instanceof Money) {
            $this->totalPrice = $totalPrice;
        } else {
            $this->totalPrice = new Money($totalPrice, $this->unitPrice->getCurrency());
        }
        return $this;
    }

    public function getTotalPriceAsFloat(): float
    {
        return $this->totalPrice->getAmountAsFloat();
    }

    public function calculateTotalPrice(): static
    {
        $this->totalPrice = $this->unitPrice->multiply((float) $this->quantity);
        return $this;
    }

    public function __toString(): string
    {
        return "{$this->productName} x {$this->quantity}";
    }
}
