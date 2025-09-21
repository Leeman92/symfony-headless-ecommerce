<?php

declare(strict_types=1);

namespace App\Domain\Enum;

use function in_array;

/**
 * User roles enumeration
 *
 * Defines available user roles in the system with
 * their string representations for Symfony security.
 */
enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * Get all available roles as array
     *
     * @return string[]
     */
    public static function getAllRoles(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }

    /**
     * Get role label for display
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ADMIN => 'Administrator',
            self::SUPER_ADMIN => 'Super Administrator',
        };
    }

    /**
     * Check if role has admin privileges
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPER_ADMIN], true);
    }

    /**
     * Get role hierarchy level (higher number = more privileges)
     */
    public function getLevel(): int
    {
        return match ($this) {
            self::USER => 1,
            self::ADMIN => 2,
            self::SUPER_ADMIN => 3,
        };
    }
}
