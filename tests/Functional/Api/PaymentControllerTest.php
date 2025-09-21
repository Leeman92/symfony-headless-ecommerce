<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Entity\Category;
use App\Domain\Entity\Product;
use App\Domain\Entity\User;
use App\Domain\Security\UserRoles;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Domain\ValueObject\Slug;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

use const JSON_THROW_ON_ERROR;

final class PaymentControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCreatePaymentIntentForUserOrder(): void
    {
        $user = $this->createUser('pay-intent@example.com');
        $token = $this->createTokenForUser($user);
        $this->authorize($token);

        $orderNumber = $this->createOrderForUser($user, 'Headphones', 'headphones', 149.99);

        $response = $this->jsonRequest('POST', sprintf('/api/payments/orders/%s/intent', $orderNumber), []);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertArrayHasKey('payment', $body['data']);
        self::assertSame($orderNumber, $body['data']['order']['order_number']);
    }

    public function testCreatePaymentIntentForGuestRequiresEmail(): void
    {
        $orderNumber = $this->createGuestOrder('guest-pay@example.com', 'Camera', 'camera', 599.99);

        $response = $this->jsonRequest('POST', sprintf('/api/payments/orders/%s/intent', $orderNumber), []);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $this->client?->request('POST', sprintf('/api/payments/orders/%s/intent?guest_email=%s', $orderNumber, urlencode('guest-pay@example.com')));
        $response = $this->client?->getResponse();
        self::assertNotNull($response);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame($orderNumber, $body['data']['order']['order_number']);
    }

    public function testConfirmPaymentIntent(): void
    {
        $user = $this->createUser('confirm@example.com');
        $token = $this->createTokenForUser($user);
        $this->authorize($token);

        $orderNumber = $this->createOrderForUser($user, 'Microphone', 'microphone', 89.99);

        $createResponse = $this->jsonRequest('POST', sprintf('/api/payments/orders/%s/intent', $orderNumber), []);
        $paymentData = $this->decodeResponse($createResponse)['data']['payment'];
        $paymentIntentId = $paymentData['stripe_payment_intent_id'];

        $confirmResponse = $this->jsonRequest('POST', sprintf('/api/payments/%s/confirm', $paymentIntentId), [
            'payment_method_id' => 'pm_test_123',
        ]);
        $body = $this->decodeResponse($confirmResponse);

        self::assertSame(Response::HTTP_OK, $confirmResponse->getStatusCode());
        self::assertSame('succeeded', $body['data']['status']);
    }

    public function testShowPaymentIntent(): void
    {
        $user = $this->createUser('show-pay@example.com');
        $token = $this->createTokenForUser($user);
        $this->authorize($token);

        $orderNumber = $this->createOrderForUser($user, 'Speaker', 'speaker', 199.99);
        $createResponse = $this->jsonRequest('POST', sprintf('/api/payments/orders/%s/intent', $orderNumber), []);
        $paymentIntentId = $this->decodeResponse($createResponse)['data']['payment']['stripe_payment_intent_id'];

        $this->client?->request('GET', sprintf('/api/payments/%s', $paymentIntentId));
        $response = $this->client?->getResponse();
        self::assertNotNull($response);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($paymentIntentId, $body['data']['stripe_payment_intent_id']);
    }

    public function testWebhookUpdatesPayment(): void
    {
        $orderNumber = $this->createGuestOrder('webhook@example.com', 'Laptop', 'laptop', 1299.99);
        $this->client?->request('POST', sprintf('/api/payments/orders/%s/intent?guest_email=%s', $orderNumber, urlencode('webhook@example.com')));
        $response = $this->client?->getResponse();
        self::assertNotNull($response);
        $decoded = $this->decodeResponse($response);
        $intentId = $decoded['data']['payment']['stripe_payment_intent_id'];

        $payload = [
            'id' => 'evt_test_webhook',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $intentId,
                    'status' => 'succeeded',
                    'payment_method' => 'pm_webhook',
                    'charges' => [
                        'data' => [
                            [
                                'payment_method_details' => [
                                    'card' => [
                                        'brand' => 'visa',
                                        'last4' => '4242',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$jsonPayload;
        $signature = hash_hmac('sha256', $signedPayload, 'whsec_test');
        $header = sprintf('t=%d,v1=%s', $timestamp, $signature);

        $this->client?->request('POST', '/api/payments/webhook', server: [
            'HTTP_STRIPE_SIGNATURE' => $header,
            'CONTENT_TYPE' => 'application/json',
        ], content: $jsonPayload);

        $response = $this->client?->getResponse();
        self::assertNotNull($response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = $this->decodeResponse($response);
        self::assertSame('succeeded', $body['data']['status']);
    }

    private function createCategory(string $name, string $slug): Category
    {
        $category = new Category($name, new Slug($slug));
        $this->entityManager?->persist($category);
        $this->entityManager?->flush();

        return $category;
    }

    private function createProduct(string $name, string $slug, float $price, int $stock): Product
    {
        $category = $this->createCategory(ucfirst($slug).' Category', $slug.'-category');
        $product = new Product($name, new Slug($slug), Money::fromFloat($price), $category);
        $product->setStock($stock);
        $product->setSku(ProductSku::fromProductName($name));
        $product->setShortDescription('Short description');
        $this->entityManager?->persist($product);
        $this->entityManager?->flush();
        $this->entityManager?->refresh($product);

        return $product;
    }

    private function createUser(string $email): User
    {
        $user = new User($email, 'Test', 'User', 'password');
        $user->setRoles(UserRoles::defaultForCustomer());
        $this->entityManager?->persist($user);
        $this->entityManager?->flush();

        return $user;
    }

    private function createOrderForUser(User $user, string $productName, string $productSlug, float $price): string
    {
        $product = $this->createProduct($productName, $productSlug, $price, 10);
        $payload = [
            'currency' => 'USD',
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
        ];

        $token = $this->createTokenForUser($user);
        $this->authorize($token);
        $response = $this->jsonRequest('POST', '/api/orders', $payload);
        $data = $this->decodeResponse($response);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        return $data['data']['order_number'];
    }

    private function createGuestOrder(string $email, string $productName, string $productSlug, float $price): string
    {
        $product = $this->createProduct($productName, $productSlug, $price, 5);
        $payload = [
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
            'guest' => [
                'email' => $email,
                'first_name' => 'Guest',
                'last_name' => 'Buyer',
            ],
        ];

        $response = $this->jsonRequest('POST', '/api/orders/guest', $payload);
        $data = $this->decodeResponse($response);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        return $data['data']['order_number'];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response): array
    {
        $content = $response->getContent();
        self::assertNotFalse($content);

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail('Invalid JSON response: '.$exception->getMessage());
        }

        self::assertIsArray($data);

        return $data;
    }
}
