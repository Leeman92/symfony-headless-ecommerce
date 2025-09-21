<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Base entity combining all common functionality
 *
 * Provides ID management, timestamps, and identifiable behavior.
 * Most domain entities should extend this class.
 */
abstract class BaseEntity extends AbstractEntity implements TimestampableInterface, IdentifiableInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;
}
