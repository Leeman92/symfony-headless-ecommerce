<?php

declare(strict_types=1);

namespace App\Application\Service\User;

/**
 * Immutable DTO that describes the data a guest provides to convert their order into a user account.
 */
final readonly class GuestAccountData
{
    public function __construct(
        private string $password,
        private ?string $firstName = null,
        private ?string $lastName = null,
    ) {
    }

    public function password(): string
    {
        return $this->password;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }
}
