<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Entity\Category;
use App\Domain\Entity\Order;
use App\Domain\Entity\Product;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ProductSku;
use App\Domain\ValueObject\Slug;
use Symfony\Component\HttpFoundation\Response;

final class OrderControllerTest extends ApiTestCase
{
    public function testGuestCheckoutCreatesOrder(): void
    {
        $product = $this->createProduct('Laptop', 'laptop', 999.99, 5);

        $payload = [
            'currency' => 'USD',
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 2,
                ],
            ],
            'guest' => [
                'email' => 'guest@example.com',
                'first_name' => 'Guest',
                'last_name' => 'User',
            ],
        ];

        $response = $this->jsonRequest('POST', '/api/orders/guest', $payload);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('guest', $body['data']['customer']['type']);
        self::assertCount(1, $body['data']['items']);
        self::assertNotEmpty($body['data']['order_number']);
    }

    public function testUserCheckoutRequiresAuthentication(): void
    {
        $response = $this->jsonRequest('POST', '/api/orders', []);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testUserCheckoutCreatesOrder(): void
    {
        $user = $this->createUser('buyer@example.com');
        $token = $this->createTokenForUser($user);
        $this->authorize($token);

        $product = $this->createProduct('Phone', 'phone', 499.99, 10);

        $payload = [
            'currency' => 'USD',
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->jsonRequest('POST', '/api/orders', $payload);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('user', $body['data']['customer']['type']);
        self::assertSame('buyer@example.com', $body['data']['customer']['email']);
    }

    public function testListUserOrdersReturnsRecentOrders(): void
    {
        $user = $this->createUser('history@example.com');
        $token = $this->createTokenForUser($user);
        $this->authorize($token);

        $product = $this->createProduct('Tablet', 'tablet', 299.99, 5);
        $payload = [
            'currency' => 'USD',
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
        ];
        $orderResponse = $this->jsonRequest('POST', '/api/orders', $payload);
        self::assertSame(Response::HTTP_CREATED, $orderResponse->getStatusCode());

        $this->client->request('GET', '/api/orders');
        $response = $this->client->getResponse();
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertCount(1, $body['data']);
        self::assertSame('history@example.com', $body['data'][0]['customer']['email']);
    }

    public function testConvertGuestOrderToUser(): void
    {
        $product = $this->createProduct('Monitor', 'monitor', 199.99, 5);
        $guestPayload = [
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
            'guest' => [
                'email' => 'guestconvert@example.com',
                'first_name' => 'Guest',
                'last_name' => 'Convert',
            ],
        ];

        $response = $this->jsonRequest('POST', '/api/orders/guest', $guestPayload);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $orderNumber = $this->decodeResponse($response)['data']['order_number'];

        $user = $this->createUser('convert@example.com');
        $token = $this->createTokenForUser($user);
        $this->authorize($token);

        $response = $this->client->request('POST', sprintf('/api/orders/%s/convert', $orderNumber));
        $response = $this->client->getResponse();
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('user', $body['data']['customer']['type']);
        self::assertSame('convert@example.com', $body['data']['customer']['email']);
    }

    public function testUpdateStatusAsAdmin(): void
    {
        $product = $this->createProduct('Camera', 'camera', 599.99, 5);
        $guestPayload = [
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
            'guest' => [
                'email' => 'camera@example.com',
                'first_name' => 'Cam',
                'last_name' => 'User',
            ],
        ];
        $response = $this->jsonRequest('POST', '/api/orders/guest', $guestPayload);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $orderNumber = $this->decodeResponse($response)['data']['order_number'];

        $token = $this->createAdminToken('admin-orders@example.com');
        $this->authorize($token);

        $payload = ['status' => Order::STATUS_CONFIRMED];
        $response = $this->jsonRequest('PATCH', sprintf('/api/orders/%s/status', $orderNumber), $payload);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Order::STATUS_CONFIRMED, $body['data']['status']);
    }

    public function testShowGuestOrderRequiresEmail(): void
    {
        $product = $this->createProduct('Printer', 'printer', 149.99, 5);
        $payload = [
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
            'guest' => [
                'email' => 'printer@example.com',
                'first_name' => 'Print',
                'last_name' => 'User',
            ],
        ];
        $response = $this->jsonRequest('POST', '/api/orders/guest', $payload);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $orderNumber = $this->decodeResponse($response)['data']['order_number'];

        $this->client->request('GET', sprintf('/api/orders/%s', $orderNumber));
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testShowGuestOrderWithEmail(): void
    {
        $product = $this->createProduct('Keyboard', 'keyboard', 89.99, 5);
        $payload = [
            'items' => [
                [
                    'product_id' => $product->getId(),
                    'quantity' => 1,
                ],
            ],
            'guest' => [
                'email' => 'keyboard@example.com',
                'first_name' => 'Key',
                'last_name' => 'Board',
            ],
        ];
        $response = $this->jsonRequest('POST', '/api/orders/guest', $payload);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $orderNumber = $this->decodeResponse($response)['data']['order_number'];

        $this->client->request('GET', sprintf('/api/orders/%s?guest_email=%s', $orderNumber, urlencode('keyboard@example.com')));
        $response = $this->client->getResponse();
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('keyboard@example.com', $body['data']['customer']['email']);
    }

    private function createCategory(string $name, string $slug): Category
    {
        $category = new Category($name, new Slug($slug));
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(string $name, string $slug, float $price, int $stock): Product
    {
        $categorySlug = $slug . '-category';
        $category = $this->createCategory(ucfirst($categorySlug), $categorySlug);

        $product = new Product($name, new Slug($slug), Money::fromFloat($price), $category);
        $product->setStock($stock);
        $product->setSku(ProductSku::fromProductName($name));
        $product->setShortDescription('Short description');
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        $this->entityManager->refresh($product);

        return $product;
    }

    private function createUser(string $email, bool $admin = false): User
    {
        $user = new User($email, 'Test', 'User', 'password');
        if ($admin) {
            $user->setRoles(['ROLE_ADMIN']);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createAdminToken(string $email): string
    {
        $user = $this->createUser($email, true);

        return $this->createTokenForUser($user);
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
        } catch (\JsonException $exception) {
            self::fail('Invalid JSON response: ' . $exception->getMessage());
        }

        self::assertIsArray($data);

        return $data;
    }
}
