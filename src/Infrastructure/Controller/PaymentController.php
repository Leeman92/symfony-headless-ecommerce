<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Service\Payment\PaymentServiceInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;
use App\Domain\Entity\User;
use App\Domain\Exception\EcommerceException;
use App\Domain\Exception\PaymentProcessingException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Infrastructure\Controller\Transformer\OrderTransformer;
use App\Infrastructure\Controller\Transformer\PaymentTransformer;
use InvalidArgumentException;
use JsonException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UnexpectedValueException;

use function is_array;
use function is_string;
use function sprintf;
use function strcasecmp;

use const JSON_THROW_ON_ERROR;

#[Route('/payments', name: 'api_payments_')]
final class PaymentController extends AbstractController
{
    private readonly string $stripeWebhookSecret;

    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        ParameterBagInterface $parameterBag,
    ) {
        $this->stripeWebhookSecret = $parameterBag->get('stripe_webhook_secret');
        if ($this->stripeWebhookSecret === '') {
            throw new InvalidArgumentException('Stripe webhook secret must be configured as a non-empty string');
        }
    }

    #[OA\Post(
        path: '/api/payments/orders/{orderNumber}/intent',
        summary: 'Create a payment intent for an order',
        tags: ['Payments'],
        security: [['Bearer' => []], []],
        parameters: [
            new OA\Parameter(name: 'orderNumber', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'guest_email', in: 'query', required: false, description: 'Required for guest access to payments.', schema: new OA\Schema(type: 'string', format: 'email')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Payment intent created.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentIntentResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Order access denied.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Order not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/orders/{orderNumber}/intent', name: 'create_intent', methods: ['POST'])]
    public function createPaymentIntent(string $orderNumber, Request $request): JsonResponse
    {
        $order = $this->findOrderOr404($orderNumber);
        $accessError = $this->guardOrderAccess($order, $request);
        if ($accessError instanceof JsonResponse) {
            return $accessError;
        }

        try {
            $payment = $this->paymentService->createPaymentIntent($order);

            return $this->json([
                'data' => [
                    'payment' => PaymentTransformer::toArray($payment),
                    'order' => OrderTransformer::toArray($order, includeItems: false, includePayment: false),
                ],
            ], Response::HTTP_CREATED);
        } catch (EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), $this->normalizeDomainStatus($exception));
        }
    }

    #[OA\Get(
        path: '/api/payments/{paymentIntentId}',
        summary: 'Fetch payment details by Stripe payment intent id',
        tags: ['Payments'],
        security: [['Bearer' => []], []],
        parameters: [
            new OA\Parameter(name: 'paymentIntentId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'guest_email', in: 'query', required: false, description: 'Required for guest-owned orders.', schema: new OA\Schema(type: 'string', format: 'email')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Payment found.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Access denied.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Payment not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/{paymentIntentId}', name: 'show', methods: ['GET'])]
    public function show(string $paymentIntentId, Request $request): JsonResponse
    {
        $payment = $this->paymentRepository->findByStripePaymentIntentId($paymentIntentId);
        if (!$payment instanceof Payment) {
            return $this->jsonError(sprintf('Payment with intent %s not found', $paymentIntentId), Response::HTTP_NOT_FOUND);
        }

        $order = $payment->getOrder();
        if ($order instanceof Order) {
            $accessError = $this->guardOrderAccess($order, $request);
            if ($accessError instanceof JsonResponse) {
                return $accessError;
            }
        }

        return $this->json(['data' => PaymentTransformer::toArray($payment)]);
    }

    #[OA\Post(
        path: '/api/payments/{paymentIntentId}/confirm',
        summary: 'Confirm a Stripe payment intent',
        tags: ['Payments'],
        security: [['Bearer' => []], []],
        parameters: [
            new OA\Parameter(name: 'paymentIntentId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'guest_email', in: 'query', required: false, description: 'Required for guest-owned orders.', schema: new OA\Schema(type: 'string', format: 'email')),
        ],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/PaymentConfirmRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Payment confirmed.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Payment confirmation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Access denied.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Payment not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/{paymentIntentId}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(string $paymentIntentId, Request $request): JsonResponse
    {
        $payment = $this->paymentRepository->findByStripePaymentIntentId($paymentIntentId);
        if (!$payment instanceof Payment) {
            return $this->jsonError(sprintf('Payment with intent %s not found', $paymentIntentId), Response::HTTP_NOT_FOUND);
        }

        $order = $payment->getOrder();
        if ($order instanceof Order) {
            $accessError = $this->guardOrderAccess($order, $request);
            if ($accessError instanceof JsonResponse) {
                return $accessError;
            }
        }

        try {
            $payload = $this->decodeJson($request, allowEmpty: true);
            $paymentMethodId = is_string($payload['payment_method_id'] ?? null)
                ? $payload['payment_method_id']
                : null;

            $confirmed = $this->paymentService->confirmPayment($paymentIntentId, $paymentMethodId);

            return $this->json(['data' => PaymentTransformer::toArray($confirmed)]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (PaymentProcessingException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), $this->normalizeDomainStatus($exception));
        }
    }

    #[OA\Post(
        path: '/api/payments/webhook',
        summary: 'Stripe webhook endpoint',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(description: 'Stripe event payload forwarded from Stripe.', required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Webhook processed.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentWebhookResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Signature verification failed.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        if ($signature === null) {
            return $this->jsonError('Missing Stripe signature header', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (SignatureVerificationException $exception) {
            return $this->jsonError('Invalid Stripe signature: '.$exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (UnexpectedValueException $exception) {
            return $this->jsonError('Invalid Stripe payload: '.$exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $payment = $this->paymentService->handleWebhookEvent($event);

        return $this->json([
            'data' => $payment !== null ? PaymentTransformer::toArray($payment) : null,
            'received' => true,
        ]);
    }

    private function guardOrderAccess(Order $order, Request $request): ?JsonResponse
    {
        $user = $this->getUser();

        if ($order->isUserOrder()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return null;
            }

            if ($user instanceof User && $order->getCustomer()?->getId() === $user->getId()) {
                return null;
            }

            return $this->jsonError('You do not have access to this payment', Response::HTTP_FORBIDDEN);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return null;
        }

        $email = $request->query->get('guest_email');
        if ($this->isGuestEmailValid($order, $email)) {
            return null;
        }

        return $this->jsonError('Guest email verification failed for this payment', Response::HTTP_FORBIDDEN);
    }

    private function isGuestEmailValid(Order $order, mixed $email): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        $guestEmail = $order->getGuestEmail();
        if ($guestEmail === null) {
            return false;
        }

        try {
            $normalized = new Email((string) $email);
        } catch (InvalidArgumentException) {
            return false;
        }

        return strcasecmp($guestEmail->getValue(), $normalized->getValue()) === 0;
    }

    private function findOrderOr404(string $orderNumber): Order
    {
        $order = $this->orderRepository->findByOrderNumber($orderNumber);

        if (!$order instanceof Order) {
            throw $this->createNotFoundException(sprintf('Order %s was not found', $orderNumber));
        }

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request, bool $allowEmpty = false): array
    {
        $content = $request->getContent();
        if ($content === '') {
            if ($allowEmpty) {
                return [];
            }
            throw new InvalidArgumentException('Request body cannot be empty');
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('JSON payload must be an object');
        }

        return $decoded;
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return $this->json([
            'error' => [
                'message' => $message,
                'status' => $status,
            ],
        ], $status);
    }

    private function normalizeDomainStatus(EcommerceException $exception): int
    {
        return $exception->getCode() >= 400 && $exception->getCode() < 600
            ? $exception->getCode()
            : Response::HTTP_BAD_REQUEST;
    }
}
