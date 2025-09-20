<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PersonName;
use App\Domain\ValueObject\Phone;
use InvalidArgumentException;

final class GuestCustomerData
{
    private Email $email;

    private PersonName $name;

    private ?Phone $phone;

    public function __construct(Email|string $email, PersonName|string $firstNameOrName, ?string $lastName = null, Phone|string|null $phone = null)
    {
        $this->email = $email instanceof Email ? $email : new Email($email);

        if ($firstNameOrName instanceof PersonName) {
            $this->name = $firstNameOrName;
        } else {
            if ($lastName === null) {
                throw new InvalidArgumentException('Last name is required when providing raw name strings');
            }

            $this->name = new PersonName($firstNameOrName, $lastName);
        }

        if ($phone instanceof Phone) {
            $this->phone = $phone;
        } elseif ($phone === null) {
            $this->phone = null;
        } else {
            $this->phone = new Phone($phone);
        }
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): PersonName
    {
        return $this->name;
    }

    public function phone(): ?Phone
    {
        return $this->phone;
    }
}
