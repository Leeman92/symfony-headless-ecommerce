<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Application\Service\User\UserAccountServiceInterface;
use App\Application\Service\User\UserRegistrationData;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Security\UserRoles;
use App\Infrastructure\Repository\UserRepository;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class AuthControllerTest extends ApiTestCase
{
    public function testRegisterCreatesAccountAndReturnsToken(): void
    {
        $payload = [
            'email' => 'new.user@example.com',
            'password' => 'securePass123',
            'first_name' => 'New',
            'last_name' => 'User',
        ];

        $response = $this->jsonRequest('POST', '/api/auth/register', $payload);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertArrayHasKey('data', $body);
        self::assertArrayHasKey('token', $body['data']);
        self::assertArrayHasKey('user', $body['data']);
        self::assertSame('new.user@example.com', $body['data']['user']['email']);
        self::assertContains(UserRoles::CUSTOMER, $body['data']['user']['roles']);

        /** @var UserRepository|null $userRepository */
        $userRepository = $this->container?->get(UserRepositoryInterface::class);
        self::assertNotNull($userRepository);
        $user = $userRepository->findActiveUserByEmail('new.user@example.com');
        self::assertInstanceOf(User::class, $user);
        self::assertNotSame('securePass123', $user->getPassword());
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $registrationService = $this->container?->get(UserAccountServiceInterface::class);
        self::assertNotNull($registrationService);
        $registrationService->register(new UserRegistrationData(
            'duplicate@example.com',
            'password123',
            'Existing',
            'User',
        ));

        $payload = [
            'email' => 'duplicate@example.com',
            'password' => 'anotherpass123',
            'first_name' => 'Dup',
            'last_name' => 'User',
        ];

        $response = $this->jsonRequest('POST', '/api/auth/register', $payload);
        $body = $this->decodeResponse($response);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertArrayHasKey('error', $body);
        self::assertSame(Response::HTTP_CONFLICT, $body['error']['status']);
    }

    public function testLoginReturnsJwtToken(): void
    {
        $registrationService = $this->container?->get(UserAccountServiceInterface::class);
        self::assertNotNull($registrationService);
        $registrationService->register(new UserRegistrationData(
            'login.user@example.com',
            'password123',
            'Login',
            'User',
        ));

        $response = $this->jsonRequest('POST', '/api/auth/login', [
            'username' => 'login.user@example.com',
            'password' => 'password123',
        ]);

        $body = $this->decodeResponse($response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('token', $body);
        self::assertNotEmpty($body['token']);
    }

    public function testRefreshReturnsNewToken(): void
    {
        $registrationService = $this->container?->get(UserAccountServiceInterface::class);
        self::assertNotNull($registrationService);
        $registrationService->register(new UserRegistrationData(
            'refresh.user@example.com',
            'password123',
            'Refresh',
            'User',
        ));

        $loginResponse = $this->jsonRequest('POST', '/api/auth/login', [
            'username' => 'refresh.user@example.com',
            'password' => 'password123',
        ]);
        $loginBody = $this->decodeResponse($loginResponse);
        self::assertSame(Response::HTTP_OK, $loginResponse->getStatusCode());
        $token = $loginBody['token'];

        $this->authorize($token);
        $refreshResponse = $this->jsonRequest('POST', '/api/auth/token/refresh');
        $refreshBody = $this->decodeResponse($refreshResponse);

        self::assertSame(Response::HTTP_OK, $refreshResponse->getStatusCode());
        self::assertArrayHasKey('data', $refreshBody);
        self::assertArrayHasKey('token', $refreshBody['data']);
        self::assertNotEmpty($refreshBody['data']['token']);
        self::assertSame('refresh.user@example.com', $refreshBody['data']['user']['email']);
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
