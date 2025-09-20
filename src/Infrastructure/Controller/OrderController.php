<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Service\Order\OrderServiceInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\Exception\EcommerceException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Infrastructure\Controller\Request\OrderRequestMapper;
use App\Infrastructure\Controller\Transformer\OrderTransformer;
use InvalidArgumentException;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;
use function strcasecmp;

#[Route('/orders', name: 'api_orders_')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderRequestMapper $requestMapper,
    ) {
    }

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

    #[Route('', name: 'create_user_order', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
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

    #[Route('', name: 'list_user_orders', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listUserOrders(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $limit = $this->extractLimit($request);

        $orders = $this->orderRepository->findRecentOrdersForUser($user, $limit);
        $data = array_map(static fn(Order $order) => OrderTransformer::toArray($order, includeItems: false), $orders);

        return $this->json([
            'data' => $data,
            'meta' => [
                'limit' => $limit,
                'count' => count($data),
            ],
        ]);
    }

    #[Route('/{orderNumber}', name: 'show', methods: ['GET'])]
    public function show(string $orderNumber, Request $request): JsonResponse
    {
        $order = $this->findOrderOr404($orderNumber);
        $user = $this->getUser();

        if ($order->isUserOrder()) {
            if (!$this->canViewUserOrder($order, $user)) {
                return $this->jsonError('You do not have access to this order', Response::HTTP_FORBIDDEN);
            }
        } else {
            $email = $request->query->get('guest_email');
            if (!$this->isGuestAccessValid($order, $email) && !$this->isGranted('ROLE_ADMIN')) {
                return $this->jsonError('Guest email verification failed for this order', Response::HTTP_FORBIDDEN);
            }
        }

        return $this->json(['data' => OrderTransformer::toArray($order)]);
    }

    #[Route('/{orderNumber}/convert', name: 'convert_guest_order', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function convertGuestOrder(string $orderNumber): JsonResponse
    {
        $order = $this->findOrderOr404($orderNumber);
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

    #[Route('/{orderNumber}/status', name: 'update_status', methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateStatus(string $orderNumber, Request $request): JsonResponse
    {
        $order = $this->findOrderOr404($orderNumber);

        try {
            $payload = $this->decodeJson($request);
            $this->applyStatusUpdate($order, $payload);
            $this->orderRepository->save($order);

            return $this->json(['data' => OrderTransformer::toArray($order)]);
        } catch (InvalidArgumentException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

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

        $this->applyOptionalTimestamp($order, 'confirmed_at', $payload, 'setConfirmedAt');
        $this->applyOptionalTimestamp($order, 'shipped_at', $payload, 'setShippedAt');
        $this->applyOptionalTimestamp($order, 'delivered_at', $payload, 'setDeliveredAt');
    }

    private function applyOptionalTimestamp(Order $order, string $key, array $payload, string $setter): void
    {
        if (!array_key_exists($key, $payload)) {
            return;
        }

        $value = $payload[$key];
        if ($value === null || $value === '') {
            $order->{$setter}(null);
            return;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('%s must be an ISO 8601 string or null', $key));
        }

        $dateTime = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value);
        if ($dateTime === false) {
            throw new InvalidArgumentException(sprintf('%s must be a valid ISO 8601 string', $key));
        }

        $order->{$setter}($dateTime);
    }

    private function findOrderOr404(string $orderNumber): Order
    {
        $order = $this->orderRepository->findByOrderNumber($orderNumber);

        if (!$order instanceof Order) {
            throw $this->createNotFoundException(sprintf('Order %s was not found', $orderNumber));
        }

        return $order;
    }

    private function canViewUserOrder(Order $order, mixed $user): bool
    {
        if (!$user instanceof User) {
            return $this->isGranted('ROLE_ADMIN');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $customer = $order->getCustomer();
        if (!$customer instanceof User) {
            return false;
        }

        return $customer->getId() === $user->getId();
    }

    private function isGuestAccessValid(Order $order, mixed $email): bool
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

    private function extractLimit(Request $request): int
    {
        $limit = (int) $request->query->get('limit', 10);
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
