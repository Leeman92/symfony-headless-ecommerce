<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PersonName;
use App\Domain\ValueObject\Phone;
use App\Infrastructure\Doctrine\Type\EmailType;
use App\Infrastructure\Doctrine\Type\PhoneType;
use App\Infrastructure\Repository\UserRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function in_array;

/**
 * User entity for authentication and customer management
 *
 * Implements Symfony security interfaces for authentication
 * and provides role-based access control.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
final class User extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface, ValidatableInterface
{
    use ValidatableTrait;

    #[ORM\Column(type: EmailType::NAME, unique: true)]
    private Email $email;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    #[ORM\Embedded(class: PersonName::class, columnPrefix: false)]
    private PersonName $name;

    #[ORM\Column(type: PhoneType::NAME, nullable: true)]
    private ?Phone $phone = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isVerified = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastLoginAt = null;

    public function __construct(
        Email|string $email,
        PersonName|string $firstName,
        string $lastName = '',
        string $password = '',
    ) {
        $this->email = $email instanceof Email ? $email : new Email($email);

        if ($firstName instanceof PersonName) {
            $this->name = $firstName;
        } else {
            $this->name = new PersonName($firstName, $lastName);
        }

        $this->password = $password;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email|string $email): static
    {
        $this->email = $email instanceof Email ? $email : new Email($email);

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email->getValue();
    }

    /**
     * @see UserInterface
     *
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_flip(array_flip($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRole(string $role): static
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_values(array_filter($this->roles, fn ($r) => $r !== $role));

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // No transient sensitive data stored on the User entity
    }

    public function getName(): PersonName
    {
        return $this->name;
    }

    public function setName(PersonName $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->name->getFirstName();
    }

    public function setFirstName(string $firstName): static
    {
        $this->name = new PersonName($firstName, $this->name->getLastName());

        return $this;
    }

    public function getLastName(): string
    {
        return $this->name->getLastName();
    }

    public function setLastName(string $lastName): static
    {
        $this->name = new PersonName($this->name->getFirstName(), $lastName);

        return $this;
    }

    public function getFullName(): string
    {
        return $this->name->getFullName();
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    public function setPhone(Phone|string|null $phone): static
    {
        if ($phone === null) {
            $this->phone = null;
        } elseif ($phone instanceof Phone) {
            $this->phone = $phone;
        } else {
            $this->phone = new Phone($phone);
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getLastLoginAt(): ?DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function updateLastLogin(): static
    {
        $this->lastLoginAt = new DateTime();

        return $this;
    }
}
