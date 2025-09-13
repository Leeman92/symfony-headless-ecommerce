<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

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
        string $country
    ) {
        $this->validateAndSetStreet($street);
        $this->validateAndSetCity($city);
        $this->validateAndSetState($state);
        $this->validateAndSetPostalCode($postalCode);
        $this->validateAndSetCountry($country);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['street'] ?? '',
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['postal_code'] ?? '',
            $data['country'] ?? ''
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

    public function equals(Address $other): bool
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

    private function validateAndSetStreet(string $street): void
    {
        $street = trim($street);
        if (empty($street)) {
            throw new InvalidArgumentException('Street address cannot be empty');
        }
        if (strlen($street) > 255) {
            throw new InvalidArgumentException('Street address cannot be longer than 255 characters');
        }
        $this->street = $street;
    }

    private function validateAndSetCity(string $city): void
    {
        $city = trim($city);
        if (empty($city)) {
            throw new InvalidArgumentException('City cannot be empty');
        }
        if (strlen($city) > 100) {
            throw new InvalidArgumentException('City cannot be longer than 100 characters');
        }
        $this->city = $city;
    }

    private function validateAndSetState(string $state): void
    {
        $state = trim($state);
        if (empty($state)) {
            throw new InvalidArgumentException('State cannot be empty');
        }
        if (strlen($state) > 100) {
            throw new InvalidArgumentException('State cannot be longer than 100 characters');
        }
        $this->state = $state;
    }

    private function validateAndSetPostalCode(string $postalCode): void
    {
        $postalCode = trim($postalCode);
        if (empty($postalCode)) {
            throw new InvalidArgumentException('Postal code cannot be empty');
        }
        if (strlen($postalCode) > 20) {
            throw new InvalidArgumentException('Postal code cannot be longer than 20 characters');
        }
        $this->postalCode = $postalCode;
    }

    private function validateAndSetCountry(string $country): void
    {
        $country = trim(strtoupper($country));
        if (empty($country)) {
            throw new InvalidArgumentException('Country cannot be empty');
        }
        if (strlen($country) !== 2) {
            throw new InvalidArgumentException('Country must be a 2-letter ISO code');
        }
        $this->country = $country;
    }
}