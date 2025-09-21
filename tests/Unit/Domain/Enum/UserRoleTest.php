<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Enum;

use App\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserRole enum
 */
final class UserRoleTest extends TestCase
{
    public function testRoleValues(): void
    {
        self::assertSame('ROLE_USER', UserRole::USER->value);
        self::assertSame('ROLE_ADMIN', UserRole::ADMIN->value);
        self::assertSame('ROLE_SUPER_ADMIN', UserRole::SUPER_ADMIN->value);
    }

    public function testGetAllRoles(): void
    {
        $allRoles = UserRole::getAllRoles();

        self::assertContains('ROLE_USER', $allRoles);
        self::assertContains('ROLE_ADMIN', $allRoles);
        self::assertContains('ROLE_SUPER_ADMIN', $allRoles);
        self::assertCount(3, $allRoles);
    }

    public function testGetLabel(): void
    {
        self::assertSame('User', UserRole::USER->getLabel());
        self::assertSame('Administrator', UserRole::ADMIN->getLabel());
        self::assertSame('Super Administrator', UserRole::SUPER_ADMIN->getLabel());
    }

    public function testIsAdmin(): void
    {
        self::assertFalse(UserRole::USER->isAdmin());
        self::assertTrue(UserRole::ADMIN->isAdmin());
        self::assertTrue(UserRole::SUPER_ADMIN->isAdmin());
    }

    public function testGetLevel(): void
    {
        self::assertSame(1, UserRole::USER->getLevel());
        self::assertSame(2, UserRole::ADMIN->getLevel());
        self::assertSame(3, UserRole::SUPER_ADMIN->getLevel());

        // Test hierarchy
        self::assertLessThan(UserRole::ADMIN->getLevel(), UserRole::USER->getLevel());
        self::assertLessThan(UserRole::SUPER_ADMIN->getLevel(), UserRole::ADMIN->getLevel());
    }
}
