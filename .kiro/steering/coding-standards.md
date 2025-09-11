# Coding Standards and Guidelines

## PHP 8.4 Modern Development Standards

This project follows strict coding standards to ensure high code quality, maintainability, and consistency.

## Core Principles

### 1. Strict Types
- **ALWAYS** use `declare(strict_types=1);` at the top of every PHP file
- Exception: Repository classes where Doctrine compatibility is required
- Enables strict type checking for scalar types

### 2. PHP 8.4 Features
- **Constructor Property Promotion**: Use when appropriate for cleaner code
- **Readonly Properties**: Use `readonly` for immutable properties
- **Final Classes**: Mark classes as `final` unless designed for inheritance
- **Typed Properties**: Always declare property types
- **Return Types**: Always declare return types for methods

### 3. PSR-12 + Symfony Standards
- Follow PSR-12 coding standard as baseline
- Use Symfony coding standards (builds on PSR-12)
- **NO Yoda Style**: Use natural comparison order (`$value === 'expected'`)

## Code Quality Tools

### PHP-CS-Fixer Configuration
```bash
# Fix code style
make cs-fix

# Check code style (dry-run)
make cs-check
```

**Rules Applied:**
- `@Symfony` ruleset
- `@Symfony:risky` for additional checks
- `@PHP84Migration` for modern PHP features
- `declare_strict_types` enforcement
- Constructor property promotion
- Modern type casting and functions

### PHPStan Static Analysis
```bash
# Run static analysis
make phpstan

# Run all quality checks
make quality
```

**Configuration:**
- Level 8 (maximum strictness)
- Symfony extension for framework-specific checks
- Doctrine compatibility exceptions for repositories

## File Structure Standards

### Class Declaration
```php
<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTime;
use DateTimeInterface;

/**
 * Product entity representing e-commerce products
 */
final class Product implements EntityInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private int $stock = 0,
    ) {}
}
```

### Interface Declaration
```php
<?php

declare(strict_types=1);

namespace App\Domain\Service;

/**
 * Service for handling product operations
 */
interface ProductServiceInterface
{
    public function createProduct(string $name, string $description): Product;
    
    public function updateStock(int $productId, int $quantity): void;
}
```

### Exception Classes
```php
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Exception thrown when product validation fails
 */
final class ProductValidationException extends EcommerceException
{
    public function __construct(string $field, string $reason)
    {
        parent::__construct("Product validation failed for {$field}: {$reason}", 400);
    }
}
```

## Type Declarations

### Scalar Types
```php
// Always use strict scalar types
public function processOrder(int $orderId, float $amount, bool $isGuest): OrderResult
{
    // Implementation
}
```

### Array Types
```php
// Use array shapes when possible (PHPStan)
/**
 * @param array{id: int, name: string, price: float} $productData
 */
public function createFromArray(array $productData): Product
{
    // Implementation
}
```

### Nullable Types
```php
// Use union types for nullable
public function findProduct(int $id): Product|null
{
    // Implementation
}

// Or traditional nullable syntax
public function getDescription(): ?string
{
    return $this->description;
}
```

## Modern PHP Features Usage

### Constructor Property Promotion
```php
// Preferred for simple DTOs and value objects
final class ProductData
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly int $stock,
    ) {}
}
```

### Arrow Functions
```php
// Use for simple transformations
$productNames = array_map(
    fn(Product $product): string => $product->getName(),
    $products
);
```

### Match Expressions
```php
// Preferred over switch for simple mappings
$status = match ($orderState) {
    OrderState::PENDING => 'Processing',
    OrderState::CONFIRMED => 'Confirmed',
    OrderState::SHIPPED => 'On the way',
    OrderState::DELIVERED => 'Delivered',
};
```

## Repository Pattern Exception

Due to Doctrine ORM compatibility requirements, repository classes have relaxed type hints:

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

/**
 * Note: Type hints are minimal to maintain Doctrine compatibility
 */
abstract class AbstractRepository extends ServiceEntityRepository implements RepositoryInterface
{
    // Doctrine methods don't use strict return types
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return parent::find($id, $lockMode, $lockVersion);
    }
}
```

## Documentation Standards

### PHPDoc Requirements
- Document all public methods with `@param` and `@return`
- Use `@throws` for exceptions
- Include class-level documentation
- Use type hints in PHPDoc for complex arrays

### Example Documentation
```php
/**
 * Service for managing e-commerce orders
 * 
 * Handles order creation, validation, and processing for both
 * authenticated users and guest customers.
 */
final class OrderService
{
    /**
     * Create a new order for a customer
     * 
     * @param array{items: array, customer_id?: int, guest_email?: string} $orderData
     * @throws InvalidOrderDataException When order data is invalid
     * @throws InsufficientStockException When product stock is insufficient
     */
    public function createOrder(array $orderData): Order
    {
        // Implementation
    }
}
```

## Testing Standards

### Test Class Structure
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProductService
 */
final class ProductServiceTest extends TestCase
{
    public function testCreateProductWithValidData(): void
    {
        // Arrange
        $service = new ProductService();
        
        // Act
        $product = $service->createProduct('Test Product', 'Description');
        
        // Assert
        self::assertInstanceOf(Product::class, $product);
        self::assertSame('Test Product', $product->getName());
    }
}
```

## CI/CD Integration

The project includes automated code quality checks:

```yaml
# GitHub Actions example
- name: Check code style
  run: make cs-check

- name: Run static analysis
  run: make phpstan

- name: Run tests
  run: make test
```

## IDE Configuration

### PhpStorm Settings
- Enable PHP 8.4 language level
- Configure PHP-CS-Fixer integration
- Enable PHPStan plugin
- Set code style to Symfony

### VS Code Extensions
- PHP Intelephense
- PHP CS Fixer
- PHPStan extension

## Enforcement

All code must pass:
1. PHP-CS-Fixer style checks
2. PHPStan level 8 analysis
3. Unit test coverage requirements
4. Manual code review

Use `make quality` to run all checks before committing code.