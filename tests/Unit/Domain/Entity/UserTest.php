<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PersonName;
use App\Domain\ValueObject\Phone;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for User entity
 */
final class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User(
            'test@example.com',
            'John',
            'Doe',
        );
    }

    public function testUserCreation(): void
    {
        self::assertSame('test@example.com', $this->user->getEmail()->getValue());
        self::assertSame('John', $this->user->getFirstName());
        self::assertSame('Doe', $this->user->getLastName());
        self::assertSame('John Doe', $this->user->getFullName());
        self::assertTrue($this->user->isActive());
        self::assertFalse($this->user->isVerified());
    }

    public function testUserIdentifier(): void
    {
        self::assertSame('test@example.com', $this->user->getUserIdentifier());
    }

    public function testDefaultRoles(): void
    {
        $roles = $this->user->getRoles();

        self::assertContains('ROLE_USER', $roles);
        self::assertCount(1, $roles);
    }

    public function testAddRole(): void
    {
        $this->user->addRole(UserRole::ADMIN->value);

        $roles = $this->user->getRoles();
        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertCount(2, $roles);
    }

    public function testAddDuplicateRole(): void
    {
        $this->user->addRole(UserRole::ADMIN->value);
        $this->user->addRole(UserRole::ADMIN->value);

        $roles = $this->user->getRoles();
        self::assertCount(2, $roles); // ROLE_USER + ROLE_ADMIN (no duplicates)
    }

    public function testRemoveRole(): void
    {
        $this->user->addRole(UserRole::ADMIN->value);
        $this->user->removeRole(UserRole::ADMIN->value);

        $roles = $this->user->getRoles();
        self::assertContains('ROLE_USER', $roles);
        self::assertNotContains('ROLE_ADMIN', $roles);
        self::assertCount(1, $roles);
    }

    public function testHasRole(): void
    {
        self::assertTrue($this->user->hasRole('ROLE_USER'));
        self::assertFalse($this->user->hasRole('ROLE_ADMIN'));

        $this->user->addRole(UserRole::ADMIN->value);
        self::assertTrue($this->user->hasRole('ROLE_ADMIN'));
    }

    public function testIsAdmin(): void
    {
        self::assertFalse($this->user->isAdmin());

        $this->user->addRole(UserRole::ADMIN->value);
        self::assertTrue($this->user->isAdmin());
    }

    public function testSetRoles(): void
    {
        $roles = [UserRole::ADMIN->value, UserRole::SUPER_ADMIN->value];
        $this->user->setRoles($roles);

        $userRoles = $this->user->getRoles();
        self::assertContains('ROLE_USER', $userRoles); // Always present
        self::assertContains('ROLE_ADMIN', $userRoles);
        self::assertContains('ROLE_SUPER_ADMIN', $userRoles);
    }

    public function testPasswordHandling(): void
    {
        $password = 'hashed_password_123';
        $this->user->setPassword($password);

        self::assertSame($password, $this->user->getPassword());
    }

    public function testEmailUpdate(): void
    {
        $newEmail = 'newemail@example.com';
        $result = $this->user->setEmail($newEmail);

        self::assertSame($this->user, $result);
        self::assertSame($newEmail, $this->user->getEmail()->getValue());
        self::assertSame($newEmail, $this->user->getUserIdentifier());

        // Test with Email value object
        $emailObject = new Email('another@example.com');
        $this->user->setEmail($emailObject);
        self::assertSame('another@example.com', $this->user->getEmail()->getValue());
    }

    public function testNameHandling(): void
    {
        $this->user->setFirstName('Jane');
        $this->user->setLastName('Smith');

        self::assertSame('Jane', $this->user->getFirstName());
        self::assertSame('Smith', $this->user->getLastName());
        self::assertSame('Jane Smith', $this->user->getFullName());

        // Test with PersonName value object
        $name = new PersonName('Alice', 'Johnson');
        $this->user->setName($name);
        self::assertSame('Alice', $this->user->getFirstName());
        self::assertSame('Johnson', $this->user->getLastName());
        self::assertSame('Alice Johnson', $this->user->getFullName());
        self::assertSame('AJ', $this->user->getName()->getInitials());
    }

    public function testPhoneHandling(): void
    {
        self::assertNull($this->user->getPhone());

        $phoneString = '+1234567890';
        $this->user->setPhone($phoneString);

        self::assertNotNull($this->user->getPhone());
        self::assertSame($phoneString, $this->user->getPhone()->getValue());

        // Test with Phone value object
        $phoneObject = new Phone('+9876543210');
        $this->user->setPhone($phoneObject);
        self::assertNotNull($this->user->getPhone());
        self::assertSame('+9876543210', $this->user->getPhone()->getValue());

        $this->user->setPhone(null);
        self::assertNull($this->user->getPhone());
    }

    public function testActiveStatus(): void
    {
        self::assertTrue($this->user->isActive());

        $this->user->setIsActive(false);
        self::assertFalse($this->user->isActive());
    }

    public function testVerificationStatus(): void
    {
        self::assertFalse($this->user->isVerified());

        $this->user->setIsVerified(true);
        self::assertTrue($this->user->isVerified());
    }

    public function testLastLoginTracking(): void
    {
        self::assertNull($this->user->getLastLoginAt());

        $loginTime = new DateTime('2024-01-01 12:00:00');
        $this->user->setLastLoginAt($loginTime);

        self::assertSame($loginTime, $this->user->getLastLoginAt());
    }

    public function testUpdateLastLogin(): void
    {
        $beforeUpdate = new DateTime();
        $this->user->updateLastLogin();
        $afterUpdate = new DateTime();

        $lastLogin = $this->user->getLastLoginAt();
        self::assertNotNull($lastLogin);
        self::assertGreaterThanOrEqual($beforeUpdate, $lastLogin);
        self::assertLessThanOrEqual($afterUpdate, $lastLogin);
    }

    public function testValidationWithValidData(): void
    {
        $user = new User(
            'valid@example.com',
            'John',
            'Doe',
        );

        self::assertTrue($user->isValid());
        self::assertEmpty($user->getValidationErrors());
    }

    public function testValidationWithInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        new User(
            'invalid-email',
            'John',
            'Doe',
        );
    }

    public function testValidationWithEmptyNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be empty');

        new User(
            'test@example.com',
            '',
            '',
        );
    }
}
