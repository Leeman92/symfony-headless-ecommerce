<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Service\User\UserAccountServiceInterface;
use App\Application\Service\User\UserRegistrationData;
use App\Domain\Entity\User;
use App\Domain\Exception\EcommerceException;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Infrastructure\Controller\Transformer\UserTransformer;
use InvalidArgumentException;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use function array_filter;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function trim;

use const JSON_THROW_ON_ERROR;

#[Route('/auth', name: 'api_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserAccountServiceInterface $userAccountService,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
    ) {
    }

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new customer account',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/RegisterRequest')),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Registration successful.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/TokenResponse')),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Validation failed.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'Email already registered.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $payload = $this->decodeJsonBody($request);
            $data = $this->buildRegistrationData($payload);
            $user = $this->userAccountService->register($data);

            return $this->buildTokenResponse($user, Response::HTTP_CREATED);
        } catch (UserAlreadyExistsException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (InvalidArgumentException|EcommerceException $exception) {
            return $this->jsonError($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[OA\Post(
        path: '/api/auth/token/refresh',
        summary: 'Refresh an existing JWT token',
        security: [['Bearer' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Token refreshed successfully.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/TokenResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Authentication required.', content: new OA\JsonContent(ref: '#App/Infrastructure/OpenApi/Schema/ErrorResponse')),
        ],
    )]
    #[Route('/token/refresh', name: 'refresh', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function refresh(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Authentication required');
        }

        return $this->buildTokenResponse($user);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildRegistrationData(array $payload): UserRegistrationData
    {
        foreach (['email', 'password', 'first_name', 'last_name'] as $field) {
            if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
                throw new InvalidArgumentException(sprintf('Field "%s" is required.', $field));
            }
        }

        $roles = [];
        if (isset($payload['roles']) && is_array($payload['roles'])) {
            $roles = array_filter($payload['roles'], static fn ($role) => is_string($role));
        }

        return new UserRegistrationData(
            $payload['email'],
            $payload['password'],
            $payload['first_name'],
            $payload['last_name'],
            array_values($roles),
        );
    }

    private function buildTokenResponse(User $user, int $status = Response::HTTP_OK): JsonResponse
    {
        $token = $this->jwtTokenManager->create($user);
        $expiresAt = null;

        try {
            $payload = $this->jwtTokenManager->parse($token);
            if (isset($payload['exp'])) {
                $expiresAt = (int) $payload['exp'];
            }
        } catch (JWTDecodeFailureException) {
            // If parsing fails we simply skip the expiration metadata
        }

        return $this->json([
            'data' => [
                'token' => $token,
                'expires_at' => $expiresAt,
                'user' => UserTransformer::toArray($user),
            ],
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonBody(Request $request): array
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
}
