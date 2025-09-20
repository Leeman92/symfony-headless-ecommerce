<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Request;

use App\Application\Service\Order\GuestCustomerData;
use App\Application\Service\Order\OrderDraft;
use App\Application\Service\Order\OrderItemDraft;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Money;
use InvalidArgumentException;

final class OrderRequestMapper
{
    /**
     * @param array<string, mixed> $payload
     */
    public function createGuestOrderData(array $payload): array
    {
        $draft = $this->createOrderDraft($payload);
        $guest = $this->createGuestCustomerData($payload['guest'] ?? []);

        return [$draft, $guest];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createOrderDraft(array $payload): OrderDraft
    {
        $itemsPayload = $payload['items'] ?? null;
        if (!is_array($itemsPayload) || $itemsPayload === []) {
            throw new InvalidArgumentException('Order must contain at least one item');
        }

        $currency = isset($payload['currency']) && $payload['currency'] !== ''
            ? strtoupper((string) $payload['currency'])
            : null;

        $items = [];
        foreach ($itemsPayload as $index => $itemPayload) {
            if (!is_array($itemPayload)) {
                throw new InvalidArgumentException(sprintf('Order item at index %d must be an object', $index));
            }

            $productId = $itemPayload['product_id'] ?? null;
            if (!is_numeric($productId)) {
                throw new InvalidArgumentException('Order item product_id is required and must be numeric');
            }

            $quantity = $itemPayload['quantity'] ?? null;
            if (!is_numeric($quantity) || (int) $quantity < 1) {
                throw new InvalidArgumentException('Order item quantity must be at least 1');
            }

            $unitPrice = null;
            if (array_key_exists('unit_price', $itemPayload) && $itemPayload['unit_price'] !== null) {
                $unitPrice = $this->parseMoney($itemPayload['unit_price'], $currency);
            }

            $items[] = new OrderItemDraft((int) $productId, (int) $quantity, $unitPrice);
        }

        return new OrderDraft(
            $items,
            $currency,
            $this->parseOptionalMoney($payload['tax'] ?? null, $currency),
            $this->parseOptionalMoney($payload['shipping'] ?? null, $currency),
            $this->parseOptionalMoney($payload['discount'] ?? null, $currency),
            $this->parseOptionalAddress($payload['billing_address'] ?? null),
            $this->parseOptionalAddress($payload['shipping_address'] ?? null),
            $this->extractNullableString($payload, 'notes'),
            $this->extractNullableArray($payload, 'metadata')
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createGuestCustomerData(array $payload): GuestCustomerData
    {
        $email = $payload['email'] ?? null;
        if (!is_string($email) || trim($email) === '') {
            throw new InvalidArgumentException('Guest email is required');
        }

        $firstName = $payload['first_name'] ?? null;
        $lastName = $payload['last_name'] ?? null;

        if (!is_string($firstName) || trim($firstName) === '') {
            throw new InvalidArgumentException('Guest first name is required');
        }

        if (!is_string($lastName) || trim($lastName) === '') {
            throw new InvalidArgumentException('Guest last name is required');
        }

        $phone = null;
        if (isset($payload['phone']) && $payload['phone'] !== '') {
            $phone = (string) $payload['phone'];
        }

        return new GuestCustomerData($email, $firstName, $lastName, $phone);
    }

    private function parseOptionalAddress(mixed $value): ?Address
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('Address must be an object');
        }

        return Address::fromArray($value);
    }

    private function parseOptionalMoney(mixed $value, ?string $currency): ?Money
    {
        if ($value === null) {
            return null;
        }

        return $this->parseMoney($value, $currency);
    }

    private function parseMoney(mixed $value, ?string $currencyHint = null): Money
    {
        if ($value instanceof Money) {
            return $value;
        }

        $currency = $currencyHint;

        if (is_array($value)) {
            if (!isset($value['amount'])) {
                throw new InvalidArgumentException('Money values must include an amount');
            }

            $currency = isset($value['currency']) && $value['currency'] !== ''
                ? strtoupper((string) $value['currency'])
                : ($currencyHint ?? 'USD');

            return new Money((string) $value['amount'], $currency);
        }

        if (is_string($value) || is_numeric($value)) {
            return new Money((string) $value, $currency ?? 'USD');
        }

        throw new InvalidArgumentException('Invalid money payload provided');
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
     * @return array<string, mixed>|null
     */
    private function extractNullableArray(array $payload, string $key): ?array
    {
        if (!array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        if (!is_array($payload[$key])) {
            throw new InvalidArgumentException(sprintf('%s must be an object', ucfirst(str_replace('_', ' ', $key))));
        }

        return $payload[$key];
    }
}
