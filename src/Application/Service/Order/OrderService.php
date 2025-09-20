<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Application\Service\Product\ProductServiceInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderItem;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidOrderDataException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class OrderService implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductServiceInterface $productService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createGuestOrder(OrderDraft $orderDraft, GuestCustomerData $guestCustomer): Order
    {
        return $this->runInTransaction(function () use ($orderDraft, $guestCustomer) {
            $order = $this->buildOrderFromDraft($orderDraft);

            $order->setGuestEmail($guestCustomer->email());
            $order->setGuestName($guestCustomer->name());
            $order->setGuestPhone($guestCustomer->phone());

            $this->assertGuestOrderValidity($order);

            $this->orderRepository->save($order);

            return $order;
        });
    }

    public function createUserOrder(User $user, OrderDraft $orderDraft): Order
    {
        return $this->runInTransaction(function () use ($user, $orderDraft) {
            $order = $this->buildOrderFromDraft($orderDraft);

            $order->setCustomer($user);

            $this->assertOrderIsValid($order);

            $this->orderRepository->save($order);

            return $order;
        });
    }

    public function convertGuestOrderToUser(Order $order, User $user): Order
    {
        if ($order->isUserOrder()) {
            throw new InvalidOrderDataException('Order is already associated with a user account');
        }

        return $this->runInTransaction(function () use ($order, $user) {
            $order->setCustomer($user);
            $order->setGuestEmail(null);
            $order->setGuestName(null);
            $order->setGuestPhone(null);

            $this->assertOrderIsValid($order);

            $this->orderRepository->save($order);

            return $order;
        });
    }

    private function buildOrderFromDraft(OrderDraft $orderDraft): Order
    {
        $currency = $orderDraft->currency();
        $order = new Order(OrderNumber::generate(), $currency ?? 'USD');
        $resolvedCurrency = $currency;
        $subtotal = null;

        foreach ($orderDraft->items() as $itemDraft) {
            $product = $this->productService->reserveStock($itemDraft->productId(), $itemDraft->quantity());

            $unitPrice = $itemDraft->unitPriceOverride() ?? $product->getPrice();
            $unitCurrency = $unitPrice->getCurrency();

            if ($resolvedCurrency === null) {
                $resolvedCurrency = $unitCurrency;
                if ($resolvedCurrency !== $order->getCurrency()) {
                    $order->setCurrency($resolvedCurrency);
                }
            } elseif ($resolvedCurrency !== $unitCurrency) {
                throw new InvalidOrderDataException('All order items must share the same currency');
            }

            $orderItem = new OrderItem($product, $itemDraft->quantity(), $unitPrice);
            $order->addItem($orderItem);

            if (!$orderItem->isValid()) {
                $errors = $orderItem->getValidationErrors();
                $this->throwInvalidOrderDataFromErrors('order item', $errors);
            }

            $subtotal ??= Money::zero($order->getCurrency());
            $subtotal = $subtotal->add($orderItem->getTotalPrice());
        }

        if ($subtotal === null) {
            throw new InvalidArgumentException('Unable to calculate order subtotal for empty order');
        }

        $order->setSubtotal($subtotal);

        $taxAmount = $orderDraft->taxAmount() ?? Money::zero($order->getCurrency());
        $shippingAmount = $orderDraft->shippingAmount() ?? Money::zero($order->getCurrency());
        $discountAmount = $orderDraft->discountAmount() ?? Money::zero($order->getCurrency());

        $this->assertMoneyCurrency($taxAmount, $order->getCurrency(), 'tax amount');
        $this->assertMoneyCurrency($shippingAmount, $order->getCurrency(), 'shipping amount');
        $this->assertMoneyCurrency($discountAmount, $order->getCurrency(), 'discount amount');

        $order->setTaxAmount($taxAmount);
        $order->setShippingAmount($shippingAmount);
        $order->setDiscountAmount($discountAmount);
        $order->calculateTotal();

        if (($billingAddress = $orderDraft->billingAddress()) !== null) {
            $order->setBillingAddress($billingAddress);
        }

        if (($shippingAddress = $orderDraft->shippingAddress()) !== null) {
            $order->setShippingAddress($shippingAddress);
        }

        if (($notes = $orderDraft->notes()) !== null) {
            $order->setNotes($notes);
        }

        if (($metadata = $orderDraft->metadata()) !== null) {
            $order->setMetadata($metadata);
        }

        $this->assertOrderIsValid($order);

        return $order;
    }

    private function assertGuestOrderValidity(Order $order): void
    {
        if ($order->getGuestEmail() === null) {
            throw new InvalidOrderDataException('Guest orders must include an email address');
        }

        if ($order->getGuestName() === null) {
            throw new InvalidOrderDataException('Guest orders must include a customer name');
        }

        $this->assertOrderIsValid($order);
    }

    private function assertOrderIsValid(Order $order): void
    {
        if ($order->getItemsCount() === 0) {
            throw new InvalidOrderDataException('Order must contain at least one item');
        }

        if (!$order->isValid()) {
            $this->throwInvalidOrderDataFromErrors('order', $order->getValidationErrors());
        }
    }

    /**
     * @param array<string, string[]> $errors
     */
    private function throwInvalidOrderDataFromErrors(string $context, array $errors): void
    {
        $firstViolation = reset($errors);
        $message = is_array($firstViolation) ? (string) ($firstViolation[0] ?? 'Unknown validation error') : 'Unknown validation error';

        throw new InvalidOrderDataException(sprintf('Validation failed for %s: %s', $context, $message));
    }

    private function assertMoneyCurrency(Money $money, string $expectedCurrency, string $field): void
    {
        if ($money->getCurrency() !== $expectedCurrency) {
            throw new InvalidOrderDataException(sprintf(
                '%s currency mismatch. Expected %s, got %s',
                ucfirst($field),
                $expectedCurrency,
                $money->getCurrency()
            ));
        }
    }

    private function runInTransaction(callable $operation)
    {
        if (method_exists($this->entityManager, 'wrapInTransaction')) {
            return $this->entityManager->wrapInTransaction($operation);
        }

        return $this->entityManager->transactional(static function ($em) use ($operation) {
            return $operation();
        });
    }
}
