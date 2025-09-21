<?php

declare(strict_types=1);

namespace App\Application\Service\User;

/**
 * Immutable DTO representing data required to register a new user account.
 */
final readonly class UserRegistrationData
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private string $email,
        private string $password,
        private string $firstName,
        private string $lastName,
        private array $roles = [],
    ) {
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->roles;
    }
}
