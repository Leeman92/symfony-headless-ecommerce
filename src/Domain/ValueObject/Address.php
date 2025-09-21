<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

use function is_scalar;
use function strlen;
use function strtoupper;
use function trim;

/**
 * Address value object
 *
 * Encapsulates address validation and behavior according to DDD principles.
 * Ensures addresses are always in a valid state.
 */
final readonly class Address
{
    private string $street;
    private string $city;
    private string $state;
    private string $postalCode;
    private string $country;

    public function __construct(
        string $street,
        string $city,
        string $state,
        string $postalCode,
        string $country,
    ) {
        $this->street = $this->validateStreet($street);
        $this->city = $this->validateCity($city);
        $this->state = $this->validateState($state);
        $this->postalCode = $this->validatePostalCode($postalCode);
        $this->country = $this->validateCountry($country);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            self::valueFromArray($data, 'street'),
            self::valueFromArray($data, 'city'),
            self::valueFromArray($data, 'state'),
            self::valueFromArray($data, 'postal_code'),
            self::valueFromArray($data, 'country'),
        );
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }

    public function getFormattedAddress(): string
    {
        return "{$this->street}, {$this->city}, {$this->state} {$this->postalCode}, {$this->country}";
    }

    public function isInCountry(string $countryCode): bool
    {
        return strtoupper($this->country) === strtoupper($countryCode);
    }

    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }

    public function __toString(): string
    {
        return $this->getFormattedAddress();
    }

    private function validateStreet(string $street): string
    {
        $street = trim($street);
        if ($street === '') {
            throw new InvalidArgumentException('Street address cannot be empty');
        }
        if (strlen($street) > 255) {
            throw new InvalidArgumentException('Street address cannot be longer than 255 characters');
        }

        return $street;
    }

    private function validateCity(string $city): string
    {
        $city = trim($city);
        if ($city === '') {
            throw new InvalidArgumentException('City cannot be empty');
        }
        if (strlen($city) > 100) {
            throw new InvalidArgumentException('City cannot be longer than 100 characters');
        }

        return $city;
    }

    private function validateState(string $state): string
    {
        $state = trim($state);
        if ($state === '') {
            throw new InvalidArgumentException('State cannot be empty');
        }
        if (strlen($state) > 100) {
            throw new InvalidArgumentException('State cannot be longer than 100 characters');
        }

        return $state;
    }

    private function validatePostalCode(string $postalCode): string
    {
        $postalCode = trim($postalCode);
        if ($postalCode === '') {
            throw new InvalidArgumentException('Postal code cannot be empty');
        }
        if (strlen($postalCode) > 20) {
            throw new InvalidArgumentException('Postal code cannot be longer than 20 characters');
        }

        return $postalCode;
    }

    private function validateCountry(string $country): string
    {
        $country = strtoupper(trim($country));
        if ($country === '') {
            throw new InvalidArgumentException('Country cannot be empty');
        }
        if (strlen($country) !== 2) {
            throw new InvalidArgumentException('Country must be a 2-letter ISO code');
        }

        return $country;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function valueFromArray(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }
}
