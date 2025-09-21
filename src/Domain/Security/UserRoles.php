<?php

declare(strict_types=1);

namespace App\Domain\Security;

final class UserRoles
{
    public const ADMIN = 'ROLE_ADMIN';
    public const CUSTOMER = 'ROLE_CUSTOMER';
    public const USER = 'ROLE_USER';

    private function __construct()
    {
    }

    /**
     * @return list<string>
     */
    public static function defaultForCustomer(): array
    {
        return [self::CUSTOMER];
    }
}
