# Stripe Payment Integration Guide

## Integration Strategy
This project uses Stripe for payment processing with both user and guest checkout support.

## Payment Flow Architecture

### Payment Intent Creation
```php
class PaymentService
{
    public function createPaymentIntent(Order $order): Payment
    {
        // Create Stripe Payment Intent
        $paymentIntent = $this->stripeClient->paymentIntents->create([
            'amount' => (int)($order->getTotal() * 100), // Convert to cents
            'currency' => 'usd',
            'metadata' => [
                'order_id' => $order->getId(),
                'customer_type' => $order->getCustomer() ? 'user' : 'guest'
            ]
        ]);
        
        // Create local payment record
        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setStripePaymentIntentId($paymentIntent->id);
        $payment->setAmount($order->getTotal());
        $payment->setCurrency('usd');
        $payment->setStatus('pending');
        
        return $payment;
    }
}
```

### Webhook Handling
- Implement secure webhook verification
- Handle payment_intent.succeeded events
- Update local payment status
- Process order fulfillment
- Handle failed payments and refunds

### Guest vs User Payments
- Both guest and user orders use same payment flow
- Store customer information in order for guests
- Link payments to user accounts when available
- Support guest-to-user conversion after payment

## Security Considerations
- Never store card details locally
- Use Stripe's secure tokenization
- Verify webhook signatures
- Implement proper error handling
- Log payment events for audit trail

## Testing Strategy
- Use Stripe test mode for development
- Test both successful and failed payments
- Verify webhook handling
- Test guest and user payment flows
- Include payment scenarios in load testing

## Environment Configuration
```yaml
# .env
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## Error Handling
- Implement custom PaymentProcessingException
- Handle network timeouts and API errors
- Provide clear error messages to users
- Log detailed error information for debugging
- Implement retry logic for transient failures