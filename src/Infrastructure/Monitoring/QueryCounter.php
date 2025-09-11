<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

/**
 * Service to count database queries for performance monitoring
 */
final class QueryCounter
{
    private int $queryCount = 0;
    private array $queries = [];

    public function increment(string $query = ''): void
    {
        $this->queryCount++;
        
        if ($query) {
            $this->queries[] = [
                'query' => $query,
                'timestamp' => microtime(true),
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ];
        }
    }

    public function getCount(): int
    {
        return $this->queryCount;
    }

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