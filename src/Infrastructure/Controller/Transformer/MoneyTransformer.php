<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Transformer;

use App\Domain\ValueObject\Money;

final class MoneyTransformer
{
    /**
     * @return array{amount: string, currency: string, amount_float: float, formatted: string}
     */
    public static function toArray(Money $money): array
    {
        return [
            'amount' => $money->getAmount(),
            'currency' => $money->getCurrency(),
            'amount_float' => $money->getAmountAsFloat(),
            'formatted' => $money->format(),
        ];
    }
}
