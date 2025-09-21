<?php

declare(strict_types=1);

namespace App\Application\Service\User;

use App\Application\Service\Order\OrderServiceInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidOrderDataException;
use App\Domain\Exception\UserAlreadyExistsException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Security\UserRoles;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PersonName;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function mb_strlen;
use function trim;

final class UserAccountService implements UserAccountServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly OrderServiceInterface $orderService,
    ) {
    }

    public function register(UserRegistrationData $data): User
    {
        $email = new Email(trim($data->email()));
        $existing = $this->userRepository->findActiveUserByEmail($email);
        if ($existing instanceof User) {
            throw new UserAlreadyExistsException('An account with this email address already exists.', 409);
        }

        $firstName = trim($data->firstName());
        $lastName = trim($data->lastName());
        if ($firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('Both first_name and last_name are required.');
        }

        $password = $data->password();
        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long.');
        }

        $user = new User($email, new PersonName($firstName, $lastName));
        $user->setRoles($this->normalizeRoles($data->roles()));
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function convertGuestOrderToUserAccount(Order $order, GuestAccountData $data): User
    {
        if ($order->isUserOrder()) {
            throw new InvalidOrderDataException('Order is already associated with a user account.');
        }

        $guestEmail = $order->getGuestEmail();
        if ($guestEmail === null) {
            throw new InvalidOrderDataException('Guest order is missing an email address and cannot be converted.');
        }

        $existing = $this->userRepository->findActiveUserByEmail($guestEmail);
        if ($existing instanceof User) {
            $this->orderService->convertGuestOrderToUser($order, $existing);

            return $existing;
        }

        $firstName = $data->firstName() ?? $order->getGuestFirstName() ?? 'Guest';
        $lastName = $data->lastName() ?? $order->getGuestLastName() ?? 'Customer';

        if (trim($firstName) === '' || trim($lastName) === '') {
            throw new InvalidArgumentException('Both first_name and last_name are required to create an account.');
        }

        $password = $data->password();
        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long.');
        }

        $user = new User($guestEmail, new PersonName(trim($firstName), trim($lastName)));
        $user->setRoles(UserRoles::defaultForCustomer());
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->orderService->convertGuestOrderToUser($order, $user);

        return $user;
    }

    /**
     * @param list<string> $roles
     * @return list<string>
     */
    private function normalizeRoles(array $roles): array
    {
        if ($roles === []) {
            return UserRoles::defaultForCustomer();
        }

        $filtered = array_filter(array_map('trim', $roles), static fn (string $role): bool => $role !== '');

        if (count($filtered) === 0) {
            return UserRoles::defaultForCustomer();
        }

        return array_values(array_unique($filtered));
    }
}
