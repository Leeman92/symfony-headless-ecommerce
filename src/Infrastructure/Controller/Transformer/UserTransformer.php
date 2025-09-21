<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Transformer;

use App\Domain\Entity\User;

final class UserTransformer
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail()->getValue(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'is_active' => $user->isActive(),
            'is_verified' => $user->isVerified(),
        ];
    }
}
