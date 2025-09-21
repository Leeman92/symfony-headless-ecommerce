<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Type\JsonbType;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use App\Domain\ValueObject\PersonName;
use App\Domain\ValueObject\Phone;
use App\Infrastructure\Doctrine\Type\AddressType;
use App\Infrastructure\Doctrine\Type\EmailType;
use App\Infrastructure\Doctrine\Type\MoneyType;
use App\Infrastructure\Doctrine\Type\OrderNumberType;
use App\Infrastructure\Doctrine\Type\PhoneType;
use App\Infrastructure\Repository\OrderRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

use function in_array;

/**
 * Order entity supporting both user and guest checkout
 *
 * Represents e-commerce orders with support for both authenticated users
 * and guest customers. Guest information is stored directly in the order.
 */
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\HasLifecycleCallbacks]
final class Order extends BaseEntity implements ValidatableInterface
{
    use ValidatableTrait;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    #[ORM\Column(type: OrderNumberType::NAME, length: 20, unique: true)]
    private OrderNumber $orderNumber;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $customer = null;

    // Guest customer information (used when customer is null)
    #[ORM\Column(type: EmailType::NAME, nullable: true)]
    private ?Email $guestEmail = null;

    #[ORM\Embedded(class: PersonName::class, columnPrefix: 'guest_')]
    private ?PersonName $guestName = null;

    #[ORM\Column(type: PhoneType::NAME, nullable: true)]
    private ?Phone $guestPhone = null;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $subtotal;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $taxAmount;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $shippingAmount;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $discountAmount;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $total;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank(message: 'Order status is required')]
    #[Assert\Choice(choices: self::VALID_STATUSES, message: 'Invalid order status')]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: AddressType::NAME, nullable: true)]
    private ?Address $billingAddress = null;

    #[ORM\Column(type: AddressType::NAME, nullable: true)]
    private ?Address $shippingAddress = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: JsonbType::NAME, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $confirmedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $shippedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $deliveredAt = null;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\OneToOne(mappedBy: 'order', targetEntity: Payment::class, cascade: ['persist', 'remove'])]
    private ?Payment $payment = null;

    public function __construct(OrderNumber $orderNumber, string $currency = 'USD')
    {
        $this->orderNumber = $orderNumber;
        $this->subtotal = Money::zero($currency);
        $this->taxAmount = Money::zero($currency);
        $this->shippingAmount = Money::zero($currency);
        $this->discountAmount = Money::zero($currency);
        $this->total = Money::zero($currency);
        $this->items = new ArrayCollection();
    }

    public function getOrderNumber(): OrderNumber
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(OrderNumber $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function isGuestOrder(): bool
    {
        return $this->customer === null;
    }

    public function isUserOrder(): bool
    {
        return $this->customer !== null;
    }

    public function getGuestEmail(): ?Email
    {
        return $this->guestEmail;
    }

    public function setGuestEmail(?Email $guestEmail): static
    {
        $this->guestEmail = $guestEmail;

        return $this;
    }

    public function getGuestName(): ?PersonName
    {
        return $this->guestName;
    }

    public function setGuestName(?PersonName $guestName): static
    {
        $this->guestName = $guestName;

        return $this;
    }

    public function getGuestFirstName(): ?string
    {
        return $this->guestName?->getFirstName();
    }

    public function setGuestFirstName(?string $guestFirstName): static
    {
        if ($guestFirstName === null) {
            $this->guestName = null;

            return $this;
        }

        $lastName = $this->guestName?->getLastName() ?? '';
        if ($lastName !== '') {
            $this->guestName = new PersonName($guestFirstName, $lastName);
        }

        return $this;
    }

    public function getGuestLastName(): ?string
    {
        return $this->guestName?->getLastName();
    }

    public function setGuestLastName(?string $guestLastName): static
    {
        if ($guestLastName === null) {
            $this->guestName = null;

            return $this;
        }

        $firstName = $this->guestName?->getFirstName() ?? '';
        if ($firstName !== '') {
            $this->guestName = new PersonName($firstName, $guestLastName);
        }

        return $this;
    }

    public function getGuestFullName(): ?string
    {
        return $this->guestName?->getFullName();
    }

    public function setGuestFullName(?string $firstName, ?string $lastName): static
    {
        if ($firstName === null || $lastName === null) {
            $this->guestName = null;

            return $this;
        }

        $this->guestName = new PersonName($firstName, $lastName);

        return $this;
    }

    public function getGuestPhone(): ?Phone
    {
        return $this->guestPhone;
    }

    public function setGuestPhone(?Phone $guestPhone): static
    {
        $this->guestPhone = $guestPhone;

        return $this;
    }

    public function getCustomerEmail(): ?Email
    {
        return $this->customer?->getEmail() ?? $this->guestEmail;
    }

    public function getCustomerEmailString(): ?string
    {
        return $this->getCustomerEmail()?->getValue();
    }

    public function getCustomerName(): ?PersonName
    {
        return $this->customer?->getName() ?? $this->guestName;
    }

    public function getCustomerNameString(): ?string
    {
        return $this->getCustomerName()?->getFullName();
    }

    public function getCustomerPhone(): ?Phone
    {
        return $this->customer?->getPhone() ?? $this->guestPhone;
    }

    public function getCustomerPhoneString(): ?string
    {
        return $this->getCustomerPhone()?->getValue();
    }

    public function getSubtotal(): Money
    {
        return $this->subtotal;
    }

    public function setSubtotal(Money $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getSubtotalAsFloat(): float
    {
        return $this->subtotal->getAmountAsFloat();
    }

    public function getTaxAmount(): Money
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(Money $taxAmount): static
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    public function getTaxAmountAsFloat(): float
    {
        return $this->taxAmount->getAmountAsFloat();
    }

    public function getShippingAmount(): Money
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(Money $shippingAmount): static
    {
        $this->shippingAmount = $shippingAmount;

        return $this;
    }

    public function getShippingAmountAsFloat(): float
    {
        return $this->shippingAmount->getAmountAsFloat();
    }

    public function getDiscountAmount(): Money
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(Money $discountAmount): static
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getDiscountAmountAsFloat(): float
    {
        return $this->discountAmount->getAmountAsFloat();
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function setTotal(Money $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getTotalAsFloat(): float
    {
        return $this->total->getAmountAsFloat();
    }

    public function calculateTotal(): static
    {
        $this->total = $this->subtotal
            ->add($this->taxAmount)
            ->add($this->shippingAmount)
            ->subtract($this->discountAmount);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid order status: {$status}");
        }

        $this->status = $status;

        // Update timestamps based on status
        match ($status) {
            self::STATUS_CONFIRMED => $this->confirmedAt ??= new DateTime(),
            self::STATUS_SHIPPED => $this->shippedAt ??= new DateTime(),
            self::STATUS_DELIVERED => $this->deliveredAt ??= new DateTime(),
            default => null,
        };

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PROCESSING, self::STATUS_SHIPPED, self::STATUS_DELIVERED], true);
    }

    public function getCurrency(): string
    {
        return $this->total->getCurrency();
    }

    public function setCurrency(string $currency): static
    {
        $currency = strtoupper($currency);

        // Update all Money objects to use the new currency
        $this->subtotal = new Money($this->subtotal->getAmount(), $currency);
        $this->taxAmount = new Money($this->taxAmount->getAmount(), $currency);
        $this->shippingAmount = new Money($this->shippingAmount->getAmount(), $currency);
        $this->discountAmount = new Money($this->discountAmount->getAmount(), $currency);
        $this->total = new Money($this->total->getAmount(), $currency);

        return $this;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?Address $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    public function setMetadataValue(string $key, mixed $value): static
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getConfirmedAt(): ?DateTimeInterface
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?DateTimeInterface $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getShippedAt(): ?DateTimeInterface
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?DateTimeInterface $shippedAt): static
    {
        $this->shippedAt = $shippedAt;

        return $this;
    }

    public function getDeliveredAt(): ?DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?DateTimeInterface $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    public function getItemsCount(): int
    {
        return $this->items->count();
    }

    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }

        return $total;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        // Set the owning side of the relation if necessary
        if ($payment !== null && $payment->getOrder() !== $this) {
            $payment->setOrder($this);
        }

        return $this;
    }

    public function hasPayment(): bool
    {
        return $this->payment !== null;
    }

    public function __toString(): string
    {
        return $this->orderNumber->getValue();
    }
}
