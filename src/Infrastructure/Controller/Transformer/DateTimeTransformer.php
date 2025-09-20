<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Transformer;

final class DateTimeTransformer
{
    public static function toString(?\DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format(DATE_ATOM);
    }
}
