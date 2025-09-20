<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PDO;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected EntityManagerInterface $entityManager;

    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();

        if (PDO::getAvailableDrivers() === []) {
            self::markTestSkipped('PDO drivers are not available in this environment.');
        }

        $this->client = self::createClient();
        $this->container = self::getContainer();
        $this->entityManager = $this->container->get(EntityManagerInterface::class);

        $this->ensureJwtKeys();

        $this->resetDatabase();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }

        unset($this->entityManager, $this->client, $this->container);

        parent::tearDown();
    }

    protected function jsonRequest(string $method, string $uri, array $payload = [], array $headers = []): Response
    {
        $server = array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_HOST' => 'traditional.ecommerce.localhost',
            'HTTPS' => true,
        ], $headers);

        $content = $payload === [] ? null : $this->encodeJson($payload);
        $this->client->request($method, $uri, server: $server, content: $content);

        return $this->client->getResponse();
    }

    protected function authorize(string $token): void
    {
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    protected function createTokenForUser(object $user): string
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = $this->container->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }

    private function resetDatabase(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);

        $schemaTool->dropDatabase();
        if ($metadata !== []) {
            $schemaTool->createSchema($metadata);
        }
    }

    private function encodeJson(array $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Failed to encode JSON payload for request', 0, $exception);
        }
    }

    private function ensureJwtKeys(): void
    {
        $projectDir = $this->container->getParameter('kernel.project_dir');
        $jwtDir = $projectDir . '/config/jwt';

        if (!is_dir($jwtDir) && !mkdir($jwtDir, 0777, true) && !is_dir($jwtDir)) {
            throw new \RuntimeException(sprintf('Unable to create JWT directory at %s', $jwtDir));
        }

        $privateKeyPath = $jwtDir . '/private.pem';
        $publicKeyPath = $jwtDir . '/public.pem';

        if (is_file($privateKeyPath) && is_file($publicKeyPath)) {
            return;
        }

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new \RuntimeException('Unable to generate JWT key pair');
        }

        $passphrase = $_ENV['JWT_PASSPHRASE'] ?? 'your_passphrase_here';

        if (!openssl_pkey_export($resource, $privateKey, $passphrase)) {
            throw new \RuntimeException('Unable to export private key for JWT');
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false || !isset($details['key'])) {
            throw new \RuntimeException('Unable to export public key for JWT');
        }

        file_put_contents($privateKeyPath, $privateKey);
        file_put_contents($publicKeyPath, $details['key']);

    }
}
