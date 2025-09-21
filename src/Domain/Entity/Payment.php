<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Type\JsonbType;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Doctrine\Type\MoneyType;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

use function in_array;

/**
 * Payment entity with Stripe integration support
 *
 * Handles payment processing for both user and guest orders
 * with comprehensive Stripe integration and status tracking.
 */
#[ORM\Entity]
#[ORM\Table(name: 'payments')]
#[ORM\HasLifecycleCallbacks]
final class Payment extends BaseEntity implements ValidatableInterface
{
    use ValidatableTrait;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public const VALID_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_SUCCEEDED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
        self::STATUS_PARTIALLY_REFUNDED,
    ];

    public const METHOD_CARD = 'card';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_WALLET = 'wallet';

    public const VALID_METHODS = [
        self::METHOD_CARD,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_WALLET,
    ];

    #[ORM\OneToOne(targetEntity: Order::class, inversedBy: 'payment', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Payment must be associated with an order')]
    private ?Order $order = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Stripe Payment Intent ID is required')]
    #[Assert\Length(max: 255, maxMessage: 'Stripe Payment Intent ID cannot be longer than {{ limit }} characters')]
    private string $stripePaymentIntentId;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Stripe Payment Method ID cannot be longer than {{ limit }} characters')]
    private ?string $stripePaymentMethodId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Stripe Customer ID cannot be longer than {{ limit }} characters')]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $amount;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $refundedAmount;

    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank(message: 'Payment status is required')]
    #[Assert\Choice(choices: self::VALID_STATUSES, message: 'Invalid payment status')]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(choices: self::VALID_METHODS, message: 'Invalid payment method')]
    private ?string $paymentMethod = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: JsonbType::NAME, nullable: true)]
    private ?array $stripeMetadata = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: JsonbType::NAME, nullable: true)]
    private ?array $paymentMethodDetails = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Failure code cannot be longer than {{ limit }} characters')]
    private ?string $failureCode = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $paidAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $failedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $refundedAt = null;

    public function __construct(
        Order $order,
        string $stripePaymentIntentId,
        Money|string $amount,
    ) {
        $this->order = $order;
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        if ($amount instanceof Money) {
            $this->amount = $amount;
        } else {
            $this->amount = new Money($amount, $order->getCurrency());
        }

        $this->refundedAmount = Money::zero($this->amount->getCurrency());
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

    public function getStripePaymentIntentId(): string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getStripePaymentMethodId(): ?string
    {
        return $this->stripePaymentMethodId;
    }

    public function setStripePaymentMethodId(?string $stripePaymentMethodId): static
    {
        $this->stripePaymentMethodId = $stripePaymentMethodId;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function setAmount(Money|string $amount): static
    {
        if ($amount instanceof Money) {
            $this->amount = $amount;
        } else {
            $this->amount = new Money($amount, $this->amount->getCurrency());
        }

        return $this;
    }

    public function getAmountAsFloat(): float
    {
        return $this->amount->getAmountAsFloat();
    }

    public function getAmountInCents(): int
    {
        return $this->amount->getAmountInCents();
    }

    public function getRefundedAmount(): Money
    {
        return $this->refundedAmount;
    }

    public function setRefundedAmount(Money|string $refundedAmount): static
    {
        if ($refundedAmount instanceof Money) {
            $this->refundedAmount = $refundedAmount;
        } else {
            $this->refundedAmount = new Money($refundedAmount, $this->amount->getCurrency());
        }

        return $this;
    }

    public function getRefundedAmountAsFloat(): float
    {
        return $this->refundedAmount->getAmountAsFloat();
    }

    public function getRemainingAmount(): Money
    {
        return $this->amount->subtract($this->refundedAmount);
    }

    public function isFullyRefunded(): bool
    {
        return $this->refundedAmount->equals($this->amount) || $this->refundedAmount->isGreaterThan($this->amount);
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->refundedAmount->isPositive() && $this->refundedAmount->isLessThan($this->amount);
    }

    public function getCurrency(): string
    {
        return $this->amount->getCurrency();
    }

    public function setCurrency(string $currency): static
    {
        $currency = strtoupper($currency);

        // Update Money objects to use new currency
        $this->amount = new Money($this->amount->getAmount(), $currency);
        $this->refundedAmount = new Money($this->refundedAmount->getAmount(), $currency);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid payment status: {$status}");
        }

        $this->status = $status;

        // Update timestamps based on status
        match ($status) {
            self::STATUS_SUCCEEDED => $this->paidAt ??= new DateTime(),
            self::STATUS_FAILED => $this->failedAt ??= new DateTime(),
            self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED => $this->refundedAt ??= new DateTime(),
            default => null,
        };

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isPartiallyRefundedStatus(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_REFUNDED;
    }

    public function canBeRefunded(): bool
    {
        return $this->isSucceeded() && !$this->isFullyRefunded();
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        if ($paymentMethod !== null && !in_array($paymentMethod, self::VALID_METHODS, true)) {
            throw new InvalidArgumentException("Invalid payment method: {$paymentMethod}");
        }

        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStripeMetadata(): ?array
    {
        return $this->stripeMetadata;
    }

    /**
     * @param array<string, mixed>|null $stripeMetadata
     */
    public function setStripeMetadata(?array $stripeMetadata): static
    {
        $this->stripeMetadata = $stripeMetadata;

        return $this;
    }

    public function getStripeMetadataValue(string $key): mixed
    {
        return $this->stripeMetadata[$key] ?? null;
    }

    public function setStripeMetadataValue(string $key, mixed $value): static
    {
        if ($this->stripeMetadata === null) {
            $this->stripeMetadata = [];
        }
        $this->stripeMetadata[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPaymentMethodDetails(): ?array
    {
        return $this->paymentMethodDetails;
    }

    /**
     * @param array<string, mixed>|null $paymentMethodDetails
     */
    public function setPaymentMethodDetails(?array $paymentMethodDetails): static
    {
        $this->paymentMethodDetails = $paymentMethodDetails;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): static
    {
        $this->failureReason = $failureReason;

        return $this;
    }

    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    public function setFailureCode(?string $failureCode): static
    {
        $this->failureCode = $failureCode;

        return $this;
    }

    public function getPaidAt(): ?DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getFailedAt(): ?DateTimeInterface
    {
        return $this->failedAt;
    }

    public function setFailedAt(?DateTimeInterface $failedAt): static
    {
        $this->failedAt = $failedAt;

        return $this;
    }

    public function getRefundedAt(): ?DateTimeInterface
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(?DateTimeInterface $refundedAt): static
    {
        $this->refundedAt = $refundedAt;

        return $this;
    }

    /**
     * @param array<string, mixed>|null $paymentMethodDetails
     */
    public function markAsSucceeded(?string $paymentMethodId = null, ?array $paymentMethodDetails = null): static
    {
        $this->setStatus(self::STATUS_SUCCEEDED);

        if ($paymentMethodId !== null) {
            $this->setStripePaymentMethodId($paymentMethodId);
        }

        if ($paymentMethodDetails !== null) {
            $this->setPaymentMethodDetails($paymentMethodDetails);
        }

        return $this;
    }

    public function markAsFailed(string $failureReason, ?string $failureCode = null): static
    {
        $this->setStatus(self::STATUS_FAILED);
        $this->setFailureReason($failureReason);

        if ($failureCode !== null) {
            $this->setFailureCode($failureCode);
        }

        return $this;
    }

    public function addRefund(Money|string $refundAmount): static
    {
        $refundMoney = $refundAmount instanceof Money ? $refundAmount : new Money($refundAmount, $this->amount->getCurrency());

        $newRefundedAmount = $this->refundedAmount->add($refundMoney);

        if ($newRefundedAmount->isGreaterThan($this->amount)) {
            throw new InvalidArgumentException('Refund amount exceeds payment amount');
        }

        $this->refundedAmount = $newRefundedAmount;

        // Update status based on refund amount
        if ($this->refundedAmount->equals($this->amount) || $this->refundedAmount->isGreaterThan($this->amount)) {
            $this->setStatus(self::STATUS_REFUNDED);
        } else {
            $this->setStatus(self::STATUS_PARTIALLY_REFUNDED);
        }

        return $this;
    }

    public function __toString(): string
    {
        return "Payment {$this->stripePaymentIntentId} - {$this->amount->format()}";
    }
}
