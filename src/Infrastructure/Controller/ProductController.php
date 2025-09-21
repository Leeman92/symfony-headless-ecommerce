<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Service\Product\ProductSearchCriteria;
use App\Application\Service\Product\ProductServiceInterface;
use App\Domain\Exception\EcommerceException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Security\UserRoles;
use App\Infrastructure\Controller\Request\ProductRequestMapper;
use App\Infrastructure\Controller\Transformer\ProductTransformer;
use InvalidArgumentException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function is_array;
use function is_string;

use const JSON_THROW_ON_ERROR;

#[Route('/products', name: 'api_products_')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductServiceInterface $productService,
        private readonly ProductRequestMapper $requestMapper,
    ) {
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'List products',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number (1-indexed).', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Maximum results per page (1-100).', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'term', in: 'query', description: 'Full-text search term matched against name and description.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', description: 'Filter products by category slug.', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Products returned successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ProductCollectionResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Invalid filtering parameters.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $criteria = $this->buildSearchCriteria($request);
            $result = $this->productService->searchProducts($criteria);

            $products = array_map(
                static fn ($product) => ProductTransformer::toArray($product),
                iterator_to_array($result),
            );

            return $this->json([
                'data' => $products,
                'meta' => [
                    'page' => $criteria->page(),
                    'limit' => $criteria->limit(),
                    'total' => $result->total(),
                    'total_pages' => $result->totalPages(),
                    'has_next' => $result->hasNextPage(),
                    'has_previous' => $result->hasPreviousPage(),
                ],
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Fetch a product by numeric identifier',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Numeric product identifier.', schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Product found.', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/{id<\\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProduct($id);

            return $this->json(['data' => ProductTransformer::toArray($product)]);
        } catch (ProductNotFoundException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    #[OA\Get(
        path: '/api/products/slug/{slug}',
        summary: 'Fetch a product by slug',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, description: 'Product slug.', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Product found.', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/slug/{slug}', name: 'show_by_slug', methods: ['GET'])]
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $product = $this->productService->getProductBySlug($slug);

            return $this->json(['data' => ProductTransformer::toArray($product)]);
        } catch (ProductNotFoundException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create a new product',
        tags: ['Products'],
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductWriteRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Product created successfully.', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Only admins can manage products.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted(UserRoles::ADMIN)]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $this->decodeJson($request);
            $product = $this->requestMapper->createProduct($payload);
            $persisted = $this->productService->createProduct($product);

            return $this->json([
                'data' => ProductTransformer::toArray($persisted),
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), $this->normalizeDomainStatus($exception));
        }
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Replace an existing product',
        tags: ['Products'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductWriteRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Product updated.', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Only admins can manage products.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[OA\Patch(
        path: '/api/products/{id}',
        summary: 'Partially update an existing product',
        tags: ['Products'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductWriteRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Product updated.', content: new OA\JsonContent(ref: '#/components/schemas/ProductResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Only admins can manage products.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/{id<\\d+>}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted(UserRoles::ADMIN)]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $product = $this->productService->getProduct($id);
            $payload = $this->decodeJson($request);
            $this->requestMapper->apply($product, $payload);
            $updated = $this->productService->updateProduct($product);

            return $this->json(['data' => ProductTransformer::toArray($updated)]);
        } catch (ProductNotFoundException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), $this->normalizeDomainStatus($exception));
        }
    }

    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Delete a product',
        tags: ['Products'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Product deleted.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Only admins can manage products.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    #[Route('/{id<\\d+>}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted(UserRoles::ADMIN)]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->productService->deleteProduct($id);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (ProductNotFoundException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    private function buildSearchCriteria(Request $request): ProductSearchCriteria
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 20);
        $limit = max(1, min(100, $limit));
        $term = $request->query->get('term');
        $category = $request->query->get('category');

        return new ProductSearchCriteria(
            is_string($term) && $term !== '' ? $term : null,
            is_string($category) && $category !== '' ? $category : null,
            $page,
            $limit,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(Request $request): array
    {
        $content = $request->getContent();
        if ($content === '') {
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
