<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Entity\Category;
use App\Domain\Entity\Product;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Slug;
use Symfony\Component\HttpFoundation\Response;

final class ProductControllerTest extends ApiTestCase
{
    public function testListProductsReturnsPaginatedResults(): void
    {
        $category = $this->createCategory('Electronics', 'electronics');
        $this->createProduct('Laptop', 'laptop', $category, '999.99');
        $this->createProduct('Phone', 'phone', $category, '499.99');

        $this->client->request('GET', '/api/products');
        $response = $this->client->getResponse();
        $payload = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('data', $payload);
        self::assertCount(2, $payload['data']);
        self::assertSame('Laptop', $payload['data'][1]['name']);
        self::assertArrayHasKey('meta', $payload);
        self::assertSame(2, $payload['meta']['total']);
    }

    public function testShowProductReturnsProductData(): void
    {
        $category = $this->createCategory('Accessories', 'accessories');
        $product = $this->createProduct('Headphones', 'headphones', $category, '199.99');

        $this->client->request('GET', sprintf('/api/products/%d', $product->getId()));
        $response = $this->client->getResponse();
        $payload = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Headphones', $payload['data']['name']);
        self::assertSame('headphones', $payload['data']['slug']);
    }

    public function testShowBySlugReturnsProduct(): void
    {
        $category = $this->createCategory('Books', 'books');
        $this->createProduct('Symfony Guide', 'symfony-guide', $category, '59.99');

        $this->client->request('GET', '/api/products/slug/symfony-guide');
        $response = $this->client->getResponse();
        $payload = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Symfony Guide', $payload['data']['name']);
    }

    public function testCreateProductRequiresAuthentication(): void
    {
        $response = $this->jsonRequest('POST', '/api/products', []);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreateProductAsAdminPersistsProduct(): void
    {
        $category = $this->createCategory('Games', 'games');
        $token = $this->createAdminToken();
        $this->authorize($token);

        $payload = [
            'name' => 'Gaming Console',
            'slug' => 'gaming-console',
            'price' => ['amount' => '399.99', 'currency' => 'USD'],
            'stock' => 10,
            'category_id' => $category->getId(),
            'description' => 'Next-gen console',
            'is_active' => true,
            'is_featured' => false,
            'track_stock' => true,
        ];

        $response = $this->jsonRequest('POST', '/api/products', $payload);
        $payload = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('Gaming Console', $payload['data']['name']);
        self::assertSame('gaming-console', $payload['data']['slug']);
    }

    public function testUpdateProductAsAdmin(): void
    {
        $category = $this->createCategory('Office', 'office');
        $product = $this->createProduct('Desk Chair', 'desk-chair', $category, '249.99');

        $token = $this->createAdminToken('admin2@example.com');
        $this->authorize($token);

        $payload = [
            'description' => 'Ergonomic desk chair',
            'is_featured' => true,
        ];

        $response = $this->jsonRequest('PATCH', sprintf('/api/products/%d', $product->getId()), $payload);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($body['data']['is_featured']);
        self::assertSame('Ergonomic desk chair', $body['data']['description']);
    }

    public function testDeleteProductAsAdmin(): void
    {
        $category = $this->createCategory('Outdoors', 'outdoors');
        $product = $this->createProduct('Tent', 'tent', $category, '299.99');

        $token = $this->createAdminToken('admin3@example.com');
        $this->authorize($token);

        $this->client->request('DELETE', sprintf('/api/products/%d', $product->getId()));
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(Product::class)->find($product->getId()));
    }

    private function createCategory(string $name, string $slug): Category
    {
        $category = new Category($name, new Slug($slug));
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createProduct(string $name, string $slug, Category $category, string $price): Product
    {
        $product = new Product($name, new Slug($slug), new Money($price), $category);
        $product->setShortDescription('Short description');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function createAdminToken(string $email = 'admin@example.com'): string
    {
        $user = new User($email, 'Admin', 'User', 'password');
        $user->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->createTokenForUser($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response): array
    {
        $content = $response->getContent();
        self::assertNotFalse($content, 'Response body should not be false');

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            self::fail('Invalid JSON response: ' . $exception->getMessage());
        }

        self::assertIsArray($data);

        return $data;
    }
}
