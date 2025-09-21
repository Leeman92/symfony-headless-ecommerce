<?php

declare(strict_types=1);

namespace App\Application\Service\Product;

use InvalidArgumentException;

use function sprintf;

final class ProductSearchCriteria
{
    private ?string $term;

    private ?string $categorySlug;

    private int $page;

    private int $limit;

    public function __construct(
        ?string $term = null,
        ?string $categorySlug = null,
        int $page = 1,
        int $limit = 20,
    ) {
        $this->term = $this->normalizeText($term);
        $this->categorySlug = $this->normalizeText($categorySlug);
        $this->page = $this->guardPositiveInt($page, 'page');
        $this->limit = $this->guardPositiveInt($limit, 'limit');
    }

    public function term(): ?string
    {
        return $this->term;
    }

    public function categorySlug(): ?string
    {
        return $this->categorySlug;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    private function guardPositiveInt(int $value, string $field): int
    {
        if ($value < 1) {
            throw new InvalidArgumentException(sprintf('%s must be greater than zero', ucfirst($field)));
        }

        return $value;
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
