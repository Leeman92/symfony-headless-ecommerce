# Domain-Driven Design and Value Objects Guide

## Value Object Strategy

This project follows Domain-Driven Design (DDD) principles with extensive use of value objects to create a rich, expressive domain model that prevents primitive obsession and encapsulates business rules.

## Core Value Objects

### Money Value Object
```php
// Instead of: string $price, string $currency
// Use: Money $price
$price = new Money('99.99', 'USD');
$total = $price->add($tax)->subtract($discount);
```

**Benefits:**
- Prevents currency mixing errors
- Handles precision correctly with decimal arithmetic
- Provides business operations (add, subtract, multiply)
- Consistent formatting and display
- Immutable and thread-safe

### Email Value Object
```php
// Instead of: string $email
// Use: Email $email
$email = new Email('user@example.com');
$domain = $email->getDomain(); // example.com
```

**Benefits:**
- Ensures valid email format always
- Provides domain-specific methods
- Prevents invalid emails from entering the system
- Consistent normalization (lowercase, trimmed)

### PersonName Value Object
```php
// Instead of: string $firstName, string $lastName
// Use: PersonName $name
$name = new PersonName('John', 'Doe');
$fullName = $name->getFullName(); // John Doe
$initials = $name->getInitials(); // JD
```

**Benefits:**
- Encapsulates name validation rules
- Provides formatting methods
- Ensures names are always valid
- Immutable and consistent

### Address Value Object
```php
// Instead of: array $address
// Use: Address $address
$address = new Address('123 Main St', 'New York', 'NY', '10001', 'US');
$formatted = $address->getFormattedAddress();
```

**Benefits:**
- Ensures complete address validation
- Provides formatting and display methods
- Can contain country-specific validation logic
- Type-safe address handling

### OrderNumber Value Object
```php
// Instead of: string $orderNumber
// Use: OrderNumber $orderNumber
$orderNumber = OrderNumber::generate(); // ORD-20241213-001
$year = $orderNumber->getYear(); // 2024
```

**Benefits:**
- Ensures consistent format
- Provides generation strategies
- Can extract metadata (year, date)
- Prevents invalid order numbers

## Implementation Guidelines

### Entity Integration
```php
final class Order extends BaseEntity
{
    private OrderNumber $orderNumber;
    private ?PersonName $guestName = null;
    private ?Email $guestEmail = null;
    private Money $subtotal;
    private Money $total;
    private ?Address $billingAddress = null;
    private ?Address $shippingAddress = null;
    
    public function __construct(OrderNumber $orderNumber)
    {
        $this->orderNumber = $orderNumber;
        $this->subtotal = Money::zero();
        $this->total = Money::zero();
    }
}
```

### Doctrine Configuration
Use Doctrine Embeddables for value objects:

```php
#[ORM\Embedded(class: Money::class)]
private Money $price;

#[ORM\Embedded(class: Address::class)]
private ?Address $billingAddress = null;
```

### Custom Doctrine Types
For complex value objects, create custom Doctrine types:

```php
// For Money value object with currency
#[ORM\Column(type: MoneyType::NAME)]
private Money $price;
```

## Value Object Design Principles

### 1. Immutability
All value objects must be immutable (readonly classes in PHP 8.1+):

```php
final readonly class Money
{
    public function __construct(
        private string $amount,
        private string $currency
    ) {}
}
```

### 2. Validation in Constructor
All validation happens in the constructor:

```php
public function __construct(string $email)
{
    $email = trim(strtolower($email));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email format');
    }
    
    $this->value = $email;
}
```

### 3. Equality Methods
Implement proper equality comparison:

```php
public function equals(Money $other): bool
{
    return $this->amount === $other->amount 
        && $this->currency === $other->currency;
}
```

### 4. Factory Methods
Provide convenient factory methods:

```php
public static function fromFloat(float $amount, string $currency = 'USD'): self
{
    return new self(number_format($amount, 2, '.', ''), $currency);
}

public static function zero(string $currency = 'USD'): self
{
    return new self('0.00', $currency);
}
```

### 5. Business Methods
Include domain-specific behavior:

```php
public function add(Money $other): self
{
    $this->ensureSameCurrency($other);
    $newAmount = $this->getAmountAsFloat() + $other->getAmountAsFloat();
    return self::fromFloat($newAmount, $this->currency);
}
```

## Testing Value Objects

### Unit Test Structure
```php
final class MoneyTest extends TestCase
{
    public function testMoneyCreation(): void
    {
        $money = new Money('99.99', 'USD');
        
        self::assertSame('99.99', $money->getAmount());
        self::assertSame('USD', $money->getCurrency());
    }
    
    public function testInvalidAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money('-10.00', 'USD');
    }
}
```

### Property-Based Testing
Consider using property-based testing for value objects to ensure invariants hold across all possible inputs.

## Migration Strategy

### Phase 1: Create Value Objects
- Implement all core value objects
- Add comprehensive unit tests
- Create Doctrine embeddables/types

### Phase 2: Update Entities
- Refactor entities to use value objects
- Update entity tests
- Maintain backward compatibility where possible

### Phase 3: Update Services
- Update service layer to work with value objects
- Refactor API serialization/deserialization
- Update validation logic

## Performance Considerations

### Memory Usage
Value objects create more objects in memory, but the benefits of type safety and encapsulation outweigh the minimal overhead.

### Doctrine Optimization
- Use embeddables for simple value objects
- Use custom types for complex value objects
- Consider lazy loading for large value objects

## Anti-Patterns to Avoid

### 1. Mutable Value Objects
```php
// DON'T DO THIS
class Money
{
    public function setAmount(string $amount): void // ❌ Mutable
    {
        $this->amount = $amount;
    }
}
```

### 2. Anemic Value Objects
```php
// DON'T DO THIS
class Money
{
    public function __construct(
        public readonly string $amount, // ❌ No behavior
        public readonly string $currency
    ) {}
}
```

### 3. Primitive Obsession
```php
// DON'T DO THIS
public function calculateTotal(
    string $subtotal,    // ❌ Primitive obsession
    string $tax,
    string $currency
): string {}

// DO THIS INSTEAD
public function calculateTotal(
    Money $subtotal,     // ✅ Rich domain model
    Money $tax
): Money {}
```

## Benefits Summary

1. **Type Safety**: Compile-time prevention of mixing different concepts
2. **Encapsulation**: Business rules are contained within value objects
3. **Expressiveness**: Code reads like the business domain
4. **Testability**: Each value object can be tested in isolation
5. **Reusability**: Value objects can be shared across entities
6. **Immutability**: Thread-safe and prevents accidental mutations
7. **Validation**: Invalid states are impossible to represent