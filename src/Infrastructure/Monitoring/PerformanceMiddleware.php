<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

use function getmypid;
use function memory_get_peak_usage;
use function memory_get_usage;
use function microtime;
use function spl_object_hash;
use function time;

use const PHP_SAPI;

/**
 * Middleware to collect performance metrics for phase comparison
 */
final class PerformanceMiddleware
{
    /**
     * @var array<string, array{start_time: float, start_memory: int, endpoint: string, method: string}>
     */
    private array $requestMetrics = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly QueryCounter $queryCounter,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = spl_object_hash($request);

        $this->requestMetrics[$requestId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'endpoint' => $request->getPathInfo(),
            'method' => $request->getMethod(),
        ];

        // Reset query counter for this request
        $this->queryCounter->reset();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = spl_object_hash($request);

        if (!isset($this->requestMetrics[$requestId])) {
            return;
        }

        $startMetrics = $this->requestMetrics[$requestId];
        $executionTime = microtime(true) - $startMetrics['start_time'];
        $memoryUsage = memory_get_usage(true) - $startMetrics['start_memory'];
        $peakMemory = memory_get_peak_usage(true);

        $metrics = [
            'phase' => $this->getCurrentPhase(),
            'infrastructure' => $this->getInfrastructureType(),
            'endpoint' => $startMetrics['endpoint'],
            'method' => $startMetrics['method'],
            'status_code' => $response->getStatusCode(),
            'execution_time' => round($executionTime * 1000, 2), // Convert to milliseconds
            'memory_usage' => $memoryUsage,
            'peak_memory' => $peakMemory,
            'query_count' => $this->queryCounter->getCount(),
            'timestamp' => time(),
            'process_id' => getmypid(),
        ];

        // Log metrics for analysis
        $this->logger->info('Performance metrics', $metrics);

        // Add performance headers for debugging
        $response->headers->set('X-Execution-Time', (string) $metrics['execution_time']);
        $response->headers->set('X-Memory-Usage', (string) $memoryUsage);
        $response->headers->set('X-Query-Count', (string) $this->queryCounter->getCount());
        $response->headers->set('X-Phase', $this->getCurrentPhase());

        // Clean up
        unset($this->requestMetrics[$requestId]);
    }

    private function getCurrentPhase(): string
    {
        return $_ENV['DEPLOYMENT_PHASE'] ?? 'development';
    }

    private function getInfrastructureType(): string
    {
        // Detect runtime type for phase comparison
        if (isset($_SERVER['FRANKENPHP_VERSION'])) {
            return 'frankenphp-worker';
        }

        if (PHP_SAPI === 'fpm-fcgi') {
            return 'php-fpm';
        }

        return 'unknown';
    }
}
