<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Money;
use InvalidArgumentException;

final class OrderDraft
{
    /** @var list<OrderItemDraft> */
    private array $items;

    private ?string $currency;

    private ?Money $taxAmount;

    private ?Money $shippingAmount;

    private ?Money $discountAmount;

    private ?Address $billingAddress;

    private ?Address $shippingAddress;

    private ?string $notes;

    private ?array $metadata;

    /**
     * @param list<OrderItemDraft> $items
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        array $items,
        ?string $currency = null,
        ?Money $taxAmount = null,
        ?Money $shippingAmount = null,
        ?Money $discountAmount = null,
        ?Address $billingAddress = null,
        ?Address $shippingAddress = null,
        ?string $notes = null,
        ?array $metadata = null,
    ) {
        if ($items === []) {
            throw new InvalidArgumentException('Order must contain at least one item');
        }

        foreach ($items as $item) {
            if (!$item instanceof OrderItemDraft) {
                throw new InvalidArgumentException('Order items must be instances of OrderItemDraft');
            }
        }

        $normalizedCurrency = $currency !== null ? strtoupper(trim($currency)) : null;
        $this->currency = $normalizedCurrency === '' ? null : $normalizedCurrency;
        $this->items = array_values($items);
        $this->taxAmount = $taxAmount;
        $this->shippingAmount = $shippingAmount;
        $this->discountAmount = $discountAmount;
        $this->billingAddress = $billingAddress;
        $this->shippingAddress = $shippingAddress;
        $this->notes = $this->normalizeNotes($notes);
        $this->metadata = $metadata === null ? null : $this->sanitizeMetadata($metadata);
    }

    /** @return list<OrderItemDraft> */
    public function items(): array
    {
        return $this->items;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function taxAmount(): ?Money
    {
        return $this->taxAmount;
    }

    public function shippingAmount(): ?Money
    {
        return $this->shippingAmount;
    }

    public function discountAmount(): ?Money
    {
        return $this->discountAmount;
    }

    public function billingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function shippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    /** @return array<string, mixed>|null */
    public function metadata(): ?array
    {
        return $this->metadata;
    }

    private function normalizeNotes(?string $notes): ?string
    {
        if ($notes === null) {
            return null;
        }

        $trimmed = trim($notes);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];
        foreach ($metadata as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
