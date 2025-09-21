<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Service\Product\ProductSearchCriteria;
use App\Application\Service\Product\ProductServiceInterface;
use App\Domain\Exception\EcommerceException;
use App\Domain\Exception\ProductNotFoundException;
use App\Infrastructure\Controller\Request\ProductRequestMapper;
use App\Infrastructure\Controller\Transformer\ProductTransformer;
use InvalidArgumentException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
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

    #[Route('/{id<\\d+>}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
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

    #[Route('/{id<\\d+>}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
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
