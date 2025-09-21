<?php

declare(strict_types=1);

namespace App\Application\Service\Product;

use App\Domain\Entity\Product;
use Countable;
use IteratorAggregate;
use Traversable;

use function ceil;
use function count;
use function max;

/**
 * @implements IteratorAggregate<int, Product>
 */
final class ProductSearchResult implements Countable, IteratorAggregate
{
    /**
     * @param list<Product> $products
     */
    public function __construct(
        private array $products,
        private int $total,
        private int $page,
        private int $limit,
    ) {
        $this->page = max(1, $page);
        $this->limit = max(1, $limit);
        $this->total = max(0, $total);
    }

    /**
     * @return list<Product>
     */
    public function products(): array
    {
        return $this->products;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function totalPages(): int
    {
        return max(1, (int) ceil($this->total / $this->limit));
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function count(): int
    {
        return count($this->products);
    }

    public function getIterator(): Traversable
    {
        yield from $this->products;
    }
}
