<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use function debug_backtrace;
use function microtime;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

/**
 * Service to count database queries for performance monitoring
 */
final class QueryCounter
{
    private int $queryCount = 0;
    /**
     * @var list<array{query: string, timestamp: float, backtrace: array<int, array<string, mixed>>}>
     */
    private array $queries = [];

    public function increment(string $query = ''): void
    {
        ++$this->queryCount;

        if ($query !== '') {
            $this->queries[] = [
                'query' => $query,
                'timestamp' => microtime(true),
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ];
        }
    }

    public function getCount(): int
    {
        return $this->queryCount;
    }

    /**
     * @return list<array{query: string, timestamp: float, backtrace: array<int, array<string, mixed>>}>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    public function reset(): void
    {
        $this->queryCount = 0;
        $this->queries = [];
    }
}
