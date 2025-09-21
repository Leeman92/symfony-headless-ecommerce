<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Service\Order\OrderServiceInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\Exception\EcommerceException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Security\UserRoles;
use App\Infrastructure\Controller\Request\OrderRequestMapper;
use App\Infrastructure\Controller\Transformer\OrderTransformer;
use App\Infrastructure\Security\Voter\OrderVoter;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function array_key_exists;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

use const DATE_ATOM;
use const JSON_THROW_ON_ERROR;

#[Route('/orders', name: 'api_orders_')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderRequestMapper $requestMapper,
    ) {
    }

    #[OA\Post(
        path: '/api/orders/guest',
        summary: 'Create a new order as a guest customer',
        tags: ['Orders'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/GuestOrderRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Guest order created.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/OrderResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Validation failed.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('/guest', name: 'guest_checkout', methods: ['POST'])]
    public function guestCheckout(Request $request): JsonResponse
    {
        try {
            $payload = $this->decodeJson($request);
            [$draft, $guest] = $this->requestMapper->createGuestOrderData($payload);
            $order = $this->orderService->createGuestOrder($draft, $guest);

            return $this->json(['data' => OrderTransformer::toArray($order)], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), $this->normalizeDomainStatus($exception));
        }
    }

    #[OA\Post(
        path: '/api/orders',
        summary: 'Create a new order for the authenticated customer',
        tags: ['Orders'],
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/UserOrderRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Order created.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/OrderResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Validation failed.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('', name: 'create_user_order', methods: ['POST'])]
    #[IsGranted(UserRoles::CUSTOMER)]
    public function createUserOrder(Request $request): JsonResponse
    {
        $user = $this->requireUser();

        try {
            $payload = $this->decodeJson($request);
            $draft = $this->requestMapper->createOrderDraft($payload);
            $order = $this->orderService->createUserOrder($user, $draft);

            return $this->json(['data' => OrderTransformer::toArray($order)], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), $this->normalizeDomainStatus($exception));
        }
    }

    #[OA\Get(
        path: '/api/orders',
        summary: 'List recent orders for the authenticated customer',
        tags: ['Orders'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', description: 'Maximum number of orders to return (1-50).', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Orders returned.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/OrderCollectionResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('', name: 'list_user_orders', methods: ['GET'])]
    #[IsGranted(UserRoles::CUSTOMER)]
    public function listUserOrders(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $limit = $this->extractLimit($request);

        $orders = $this->orderRepository->findRecentOrdersForUser($user, $limit);
        $data = array_map(static fn (Order $order) => OrderTransformer::toArray($order, includeItems: false), $orders);

        return $this->json([
            'data' => $data,
            'meta' => [
                'limit' => $limit,
                'count' => count($data),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/orders/{orderNumber}',
        summary: 'Fetch a single order by order number',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'orderNumber', in: 'path', required: true, description: 'Order number reference.', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'guest_email', in: 'query', required: false, description: 'Required for guest access to their order.', schema: new OA\Schema(type: 'string', format: 'email')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Order found.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/OrderResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Access denied.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Order not found.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('/{orderNumber}', name: 'show', methods: ['GET'])]
    public function show(string $orderNumber, Request $request): JsonResponse
    {
        $order = $this->findOrderOr404($orderNumber);
        $user = $this->getUser();

        $guestEmail = $request->query->get('guest_email');
        $context = [
            'order' => $order,
            'guest_email' => is_string($guestEmail) ? $guestEmail : null,
        ];

        $this->denyAccessUnlessGranted(OrderVoter::VIEW, $context);

        return $this->json(['data' => OrderTransformer::toArray($order)]);
    }

    #[OA\Post(
        path: '/api/orders/{orderNumber}/convert',
        summary: 'Convert a guest order into the current user account',
        tags: ['Orders'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'orderNumber', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Order converted.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/OrderResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Conversion failed.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Access denied.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Order not found.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('/{orderNumber}/convert', name: 'convert_guest_order', methods: ['POST'])]
    public function convertGuestOrder(string $orderNumber): JsonResponse
    {
        $order = $this->findOrderOr404($orderNumber);
        $this->denyAccessUnlessGranted(OrderVoter::CONVERT, $order);
        $user = $this->requireUser();

        try {
            $converted = $this->orderService->convertGuestOrderToUser($order, $user);

            return $this->json(['data' => OrderTransformer::toArray($converted)]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), $this->normalizeDomainStatus($exception));
        }
    }

    #[OA\Patch(
        path: '/api/orders/{orderNumber}/status',
        summary: 'Update the status of an order',
        tags: ['Orders'],
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'orderNumber', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/OrderStatusUpdateRequest')),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Order status updated.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/OrderResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Validation failed.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Access denied.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Order not found.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('/{orderNumber}/status', name: 'update_status', methods: ['PATCH'])]
    #[IsGranted(UserRoles::ADMIN)]
    public function updateStatus(string $orderNumber, Request $request): JsonResponse
    {
        $order = $this->findOrderOr404($orderNumber);
        $this->denyAccessUnlessGranted(OrderVoter::UPDATE_STATUS, $order);

        try {
            $payload = $this->decodeJson($request);
            $this->applyStatusUpdate($order, $payload);
            $this->orderRepository->save($order);

            return $this->json(['data' => OrderTransformer::toArray($order)]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyStatusUpdate(Order $order, array $payload): void
    {
        if (!array_key_exists('status', $payload) || !is_string($payload['status'])) {
            throw new InvalidArgumentException('Order status is required');
        }

        $status = strtolower(trim($payload['status']));
        if (!in_array($status, Order::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid order status "%s"', $status));
        }

        $order->setStatus($status);

        if (array_key_exists('notes', $payload) && is_string($payload['notes'])) {
            $order->setNotes(trim($payload['notes']));
        }

        $this->applyOptionalTimestamp(
            $order,
            'confirmed_at',
            $payload,
            static function (Order $target, ?DateTimeImmutable $datetime): void {
                $target->setConfirmedAt($datetime);
            },
        );
        $this->applyOptionalTimestamp(
            $order,
            'shipped_at',
            $payload,
            static function (Order $target, ?DateTimeImmutable $datetime): void {
                $target->setShippedAt($datetime);
            },
        );
        $this->applyOptionalTimestamp(
            $order,
            'delivered_at',
            $payload,
            static function (Order $target, ?DateTimeImmutable $datetime): void {
                $target->setDeliveredAt($datetime);
            },
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param callable(Order, ?DateTimeImmutable): void $setter
     */
    private function applyOptionalTimestamp(Order $order, string $key, array $payload, callable $setter): void
    {
        if (!array_key_exists($key, $payload)) {
            return;
        }

        $value = $payload[$key];
        if ($value === null || $value === '') {
            $setter($order, null);

            return;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s must be an ISO 8601 string or null', $key));
        }

        $dateTime = DateTimeImmutable::createFromFormat(DATE_ATOM, $value);
        if ($dateTime === false) {
            throw new InvalidArgumentException(sprintf('%s must be a valid ISO 8601 string', $key));
        }

        $setter($order, $dateTime);
    }

    private function findOrderOr404(string $orderNumber): Order
    {
        $order = $this->orderRepository->findByOrderNumber($orderNumber);

        if (!$order instanceof Order) {
            throw $this->createNotFoundException(sprintf('Order %s was not found', $orderNumber));
        }

        return $order;
    }

    private function extractLimit(Request $request): int
    {
        $limit = $request->query->getInt('limit', 10);
        $limit = max(1, min(50, $limit));

        return $limit;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required');
        }

        return $user;
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
