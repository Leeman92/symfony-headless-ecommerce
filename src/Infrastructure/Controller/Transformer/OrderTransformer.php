<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Transformer;

use App\Domain\Entity\Order;
use App\Domain\Entity\OrderItem;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PersonName;
use App\Domain\ValueObject\Phone;

final class OrderTransformer
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Order $order, bool $includeItems = true, bool $includePayment = true): array
    {
        $data = [
            'id' => $order->getId(),
            'order_number' => (string) $order->getOrderNumber(),
            'status' => $order->getStatus(),
            'currency' => $order->getCurrency(),
            'subtotal' => MoneyTransformer::toArray($order->getSubtotal()),
            'tax' => MoneyTransformer::toArray($order->getTaxAmount()),
            'shipping' => MoneyTransformer::toArray($order->getShippingAmount()),
            'discount' => MoneyTransformer::toArray($order->getDiscountAmount()),
            'total' => MoneyTransformer::toArray($order->getTotal()),
            'notes' => $order->getNotes(),
            'metadata' => $order->getMetadata(),
            'customer' => self::transformCustomer($order),
            'billing_address' => self::transformAddress($order->getBillingAddress()),
            'shipping_address' => self::transformAddress($order->getShippingAddress()),
            'confirmed_at' => DateTimeTransformer::toString($order->getConfirmedAt()),
            'shipped_at' => DateTimeTransformer::toString($order->getShippedAt()),
            'delivered_at' => DateTimeTransformer::toString($order->getDeliveredAt()),
            'created_at' => DateTimeTransformer::toString($order->getCreatedAt()),
            'updated_at' => DateTimeTransformer::toString($order->getUpdatedAt()),
        ];

        if ($includeItems) {
            $data['items'] = array_map(static fn (OrderItem $item) => self::transformItem($item), $order->getItems()->toArray());
        }

        if ($includePayment && $order->getPayment() !== null) {
            $data['payment'] = PaymentTransformer::toArray($order->getPayment());
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private static function transformItem(OrderItem $item): array
    {
        return [
            'id' => $item->getId(),
            'product_id' => $item->getProductId(),
            'product_name' => $item->getProductName(),
            'product_sku' => $item->getProductSku()?->getValue(),
            'quantity' => $item->getQuantity(),
            'unit_price' => MoneyTransformer::toArray($item->getUnitPrice()),
            'total_price' => MoneyTransformer::toArray($item->getTotalPrice()),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function transformCustomer(Order $order): ?array
    {
        if ($order->isUserOrder() && $order->getCustomer() instanceof User) {
            return self::transformUser($order->getCustomer());
        }

        if ($order->isGuestOrder()) {
            return self::transformGuest(
                $order->getGuestEmail(),
                $order->getGuestName(),
                $order->getGuestPhone(),
            );
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function transformUser(User $user): array
    {
        return [
            'type' => 'user',
            'id' => $user->getId(),
            'email' => $user->getEmail()->getValue(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function transformGuest(?Email $email, ?PersonName $name, ?Phone $phone): array
    {
        return [
            'type' => 'guest',
            'email' => $email?->getValue(),
            'first_name' => $name?->getFirstName(),
            'last_name' => $name?->getLastName(),
            'full_name' => $name?->getFullName(),
            'phone' => $phone?->getValue(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function transformAddress(?Address $address): ?array
    {
        return $address?->toArray();
    }
}
