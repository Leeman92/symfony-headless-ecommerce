<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Category;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderItem;
use App\Domain\Entity\Payment;
use App\Domain\Entity\Product;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderNumber;
use App\Domain\ValueObject\PersonName;
use App\Domain\ValueObject\Phone;
use App\Domain\ValueObject\Slug;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Order entity
 */
final class OrderTest extends TestCase
{
    private Order $order;

    protected function setUp(): void
    {
        $orderNumber = new OrderNumber('ORD-2024-001');
        $this->order = new Order($orderNumber);
    }

    public function testOrderCreation(): void
    {
        self::assertSame('ORD-2024-001', $this->order->getOrderNumber()->getValue());
        self::assertSame(Order::STATUS_PENDING, $this->order->getStatus());
        self::assertSame('USD', $this->order->getCurrency());
        self::assertSame('0.00', $this->order->getSubtotal()->getAmount());
        self::assertSame('0.00', $this->order->getTotal()->getAmount());
        self::assertTrue($this->order->isGuestOrder());
        self::assertFalse($this->order->isUserOrder());
    }

    public function testUserOrderAssociation(): void
    {
        $user = new User('test@example.com', 'John', 'Doe');
        $this->order->setCustomer($user);

        self::assertSame($user, $this->order->getCustomer());
        self::assertFalse($this->order->isGuestOrder());
        self::assertTrue($this->order->isUserOrder());
        // Note: These will need updating when User entity uses value objects
        // self::assertSame('test@example.com', $this->order->getCustomerEmailString());
        // self::assertSame('John Doe', $this->order->getCustomerNameString());
    }

    public function testGuestOrderInformation(): void
    {
        $email = new Email('guest@example.com');
        $name = new PersonName('Jane', 'Smith');
        $phone = new Phone('+1234567890');
        
        $this->order->setGuestEmail($email);
        $this->order->setGuestName($name);
        $this->order->setGuestPhone($phone);

        self::assertSame('guest@example.com', $this->order->getGuestEmail()->getValue());
        self::assertSame('Jane', $this->order->getGuestFirstName());
        self::assertSame('Smith', $this->order->getGuestLastName());
        self::assertSame('Jane Smith', $this->order->getGuestFullName());
        self::assertSame('+1234567890', $this->order->getGuestPhone()->getValue());
        
        // Test customer methods for guest order
        self::assertSame('guest@example.com', $this->order->getCustomerEmailString());
        self::assertSame('Jane Smith', $this->order->getCustomerNameString());
        self::assertSame('+1234567890', $this->order->getCustomerPhoneString());
    }

    public function testGuestFullNameWithPartialNames(): void
    {
        // Test with PersonName value object
        $name = new PersonName('Jane', 'Smith');
        $this->order->setGuestName($name);
        self::assertSame('Jane Smith', $this->order->getGuestFullName());

        // Test with no name
        $this->order->setGuestName(null);
        self::assertNull($this->order->getGuestFullName());
    }

    public function testOrderAmounts(): void
    {
        $subtotal = new Money('100.00', 'USD');
        $tax = new Money('8.50', 'USD');
        $shipping = new Money('15.00', 'USD');
        $discount = new Money('5.00', 'USD');
        
        $this->order->setSubtotal($subtotal);
        $this->order->setTaxAmount($tax);
        $this->order->setShippingAmount($shipping);
        $this->order->setDiscountAmount($discount);

        self::assertSame('100.00', $this->order->getSubtotal()->getAmount());
        self::assertSame(100.0, $this->order->getSubtotalAsFloat());
        self::assertSame('8.50', $this->order->getTaxAmount()->getAmount());
        self::assertSame(8.5, $this->order->getTaxAmountAsFloat());
        self::assertSame('15.00', $this->order->getShippingAmount()->getAmount());
        self::assertSame(15.0, $this->order->getShippingAmountAsFloat());
        self::assertSame('5.00', $this->order->getDiscountAmount()->getAmount());
        self::assertSame(5.0, $this->order->getDiscountAmountAsFloat());
    }

    public function testCalculateTotal(): void
    {
        $this->order->setSubtotal(new Money('100.00', 'USD'));
        $this->order->setTaxAmount(new Money('8.50', 'USD'));
        $this->order->setShippingAmount(new Money('15.00', 'USD'));
        $this->order->setDiscountAmount(new Money('5.00', 'USD'));
        
        $this->order->calculateTotal();
        
        // 100.00 + 8.50 + 15.00 - 5.00 = 118.50
        self::assertSame('118.50', $this->order->getTotal()->getAmount());
        self::assertSame(118.5, $this->order->getTotalAsFloat());
    }

    public function testOrderStatusManagement(): void
    {
        // Test initial status
        self::assertTrue($this->order->isPending());
        self::assertFalse($this->order->isConfirmed());

        // Test status change to confirmed
        $this->order->setStatus(Order::STATUS_CONFIRMED);
        self::assertTrue($this->order->isConfirmed());
        self::assertFalse($this->order->isPending());
        self::assertNotNull($this->order->getConfirmedAt());

        // Test status change to shipped
        $this->order->setStatus(Order::STATUS_SHIPPED);
        self::assertTrue($this->order->isShipped());
        self::assertNotNull($this->order->getShippedAt());

        // Test status change to delivered
        $this->order->setStatus(Order::STATUS_DELIVERED);
        self::assertTrue($this->order->isDelivered());
        self::assertNotNull($this->order->getDeliveredAt());
    }

    public function testInvalidOrderStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order status: invalid_status');
        
        $this->order->setStatus('invalid_status');
    }

    public function testOrderStatusPermissions(): void
    {
        // Pending order can be cancelled
        self::assertTrue($this->order->canBeCancelled());
        self::assertFalse($this->order->canBeRefunded());

        // Confirmed order can be cancelled and refunded
        $this->order->setStatus(Order::STATUS_CONFIRMED);
        self::assertTrue($this->order->canBeCancelled());
        self::assertTrue($this->order->canBeRefunded());

        // Shipped order cannot be cancelled but can be refunded
        $this->order->setStatus(Order::STATUS_SHIPPED);
        self::assertFalse($this->order->canBeCancelled());
        self::assertTrue($this->order->canBeRefunded());

        // Cancelled order cannot be cancelled or refunded
        $this->order->setStatus(Order::STATUS_CANCELLED);
        self::assertFalse($this->order->canBeCancelled());
        self::assertFalse($this->order->canBeRefunded());
    }

    public function testCurrencyHandling(): void
    {
        $this->order->setCurrency('eur');
        self::assertSame('EUR', $this->order->getCurrency());
        
        $this->order->setCurrency('GBP');
        self::assertSame('GBP', $this->order->getCurrency());
    }

    public function testAddressHandling(): void
    {
        $billingAddress = new Address('123 Main St', 'New York', 'NY', '10001', 'US');
        $shippingAddress = new Address('456 Oak Ave', 'Los Angeles', 'CA', '90210', 'US');

        $this->order->setBillingAddress($billingAddress);
        $this->order->setShippingAddress($shippingAddress);

        self::assertSame($billingAddress, $this->order->getBillingAddress());
        self::assertSame($shippingAddress, $this->order->getShippingAddress());
        
        // Test formatted addresses
        self::assertSame('123 Main St, New York, NY 10001, US', $this->order->getBillingAddress()->getFormattedAddress());
        self::assertSame('456 Oak Ave, Los Angeles, CA 90210, US', $this->order->getShippingAddress()->getFormattedAddress());
    }

    public function testMetadataHandling(): void
    {
        $metadata = ['source' => 'web', 'campaign' => 'summer2024'];
        $this->order->setMetadata($metadata);

        self::assertSame($metadata, $this->order->getMetadata());
        self::assertSame('web', $this->order->getMetadataValue('source'));
        self::assertSame('summer2024', $this->order->getMetadataValue('campaign'));
        self::assertNull($this->order->getMetadataValue('nonexistent'));

        $this->order->setMetadataValue('new_key', 'new_value');
        self::assertSame('new_value', $this->order->getMetadataValue('new_key'));
    }

    public function testOrderItemsManagement(): void
    {
        $category = new Category('Electronics', Slug::fromString('electronics'));
        $product = new Product('Test Product', Slug::fromString('test-product'), new Money('99.99'), $category);
        $orderItem = new OrderItem($product, 2);

        self::assertSame(0, $this->order->getItemsCount());
        self::assertSame(0, $this->order->getTotalQuantity());

        $this->order->addItem($orderItem);

        self::assertSame(1, $this->order->getItemsCount());
        self::assertSame(2, $this->order->getTotalQuantity());
        self::assertTrue($this->order->getItems()->contains($orderItem));
        self::assertSame($this->order, $orderItem->getOrder());

        $this->order->removeItem($orderItem);

        self::assertSame(0, $this->order->getItemsCount());
        self::assertSame(0, $this->order->getTotalQuantity());
        self::assertFalse($this->order->getItems()->contains($orderItem));
    }

    public function testPaymentAssociation(): void
    {
        self::assertFalse($this->order->hasPayment());
        self::assertNull($this->order->getPayment());

        $payment = new Payment($this->order, 'pi_test123', '100.00');
        $this->order->setPayment($payment);

        self::assertTrue($this->order->hasPayment());
        self::assertSame($payment, $this->order->getPayment());
        self::assertSame($this->order, $payment->getOrder());
    }

    public function testOrderValidation(): void
    {
        // Test valid order
        $this->order->setGuestEmail(new Email('test@example.com'));
        $this->order->setGuestFirstName('John');
        $this->order->setGuestLastName('Doe');
        $this->order->setSubtotal(new Money('100.00'));
        $this->order->setTotal(new Money('100.00'));

        self::assertTrue($this->order->isValid());
        self::assertEmpty($this->order->getValidationErrors());
    }

    public function testOrderToString(): void
    {
        self::assertSame('ORD-2024-001', (string) $this->order);
    }
}
