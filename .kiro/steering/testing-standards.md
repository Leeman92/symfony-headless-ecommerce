# Testing Standards and Guidelines

## Test Pyramid Structure
- **70% Unit Tests:** Entities, services, repositories
- **20% Integration Tests:** API endpoints, database integration  
- **10% End-to-End Tests:** Complete user journeys

## Testing Framework Configuration
```xml
<!-- phpunit.xml.dist -->
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Functional">
            <directory>tests/Functional</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

## Unit Testing Standards

### Entity Testing
```php
class ProductTest extends TestCase
{
    public function testProductValidation(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice('99.99');
        $product->setStock(10);
        
        $this->assertTrue($product->isValid());
    }
    
    public function testJsonAttributeHandling(): void
    {
        $product = new Product();
        $attributes = ['color' => 'red', 'size' => 'M'];
        $product->setAttributes($attributes);
        
        $this->assertEquals($attributes, $product->getAttributes());
    }
}
```

### Service Testing
```php
class OrderServiceTest extends TestCase
{
    public function testGuestOrderCreation(): void
    {
        $orderData = [
            'items' => [['product_id' => 1, 'quantity' => 2]],
            'guest_email' => 'guest@example.com',
            'guest_name' => 'John Doe'
        ];
        
        $order = $this->orderService->createOrder($orderData);
        
        $this->assertNull($order->getCustomer());
        $this->assertEquals('guest@example.com', $order->getGuestEmail());
    }
    
    public function testUserOrderCreation(): void
    {
        $user = $this->createUser();
        $orderData = [
            'items' => [['product_id' => 1, 'quantity' => 2]],
            'customer_id' => $user->getId()
        ];
        
        $order = $this->orderService->createOrder($orderData);
        
        $this->assertEquals($user, $order->getCustomer());
        $this->assertNull($order->getGuestEmail());
    }
}
```

## Integration Testing Standards

### API Testing
```php
class ProductApiTest extends ApiTestCase
{
    public function testProductListing(): void
    {
        $this->client->request('GET', '/api/products');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }
    
    public function testGuestCheckout(): void
    {
        $orderData = [
            'items' => [['product_id' => 1, 'quantity' => 2]],
            'guest_email' => 'guest@example.com',
            'guest_name' => 'John Doe'
        ];
        
        $this->client->request('POST', '/api/orders/guest', [], [], [], json_encode($orderData));
        
        $this->assertResponseStatusCodeSame(201);
    }
}
```

### Database Integration Testing
```php
class ProductRepositoryTest extends KernelTestCase
{
    public function testFindByCriteria(): void
    {
        $repository = $this->getContainer()->get(ProductRepository::class);
        
        $criteria = ['category' => 'Electronics', 'min_price' => 100];
        $products = $repository->findByCriteria($criteria);
        
        $this->assertNotEmpty($products);
        foreach ($products as $product) {
            $this->assertEquals('Electronics', $product->getCategory()->getName());
            $this->assertGreaterThanOrEqual(100, $product->getPrice());
        }
    }
}
```

## End-to-End Testing

### Complete User Journeys
```php
class CheckoutWorkflowTest extends WebTestCase
{
    public function testCompleteGuestCheckoutFlow(): void
    {
        // Browse products
        $this->client->request('GET', '/api/products');
        $this->assertResponseIsSuccessful();
        
        // Add to cart (guest)
        $cartData = ['product_id' => 1, 'quantity' => 2];
        $this->client->request('POST', '/api/cart/guest', [], [], [], json_encode($cartData));
        $this->assertResponseIsSuccessful();
        
        // Checkout as guest
        $orderData = [
            'items' => [['product_id' => 1, 'quantity' => 2]],
            'guest_email' => 'guest@example.com',
            'guest_name' => 'John Doe'
        ];
        $this->client->request('POST', '/api/orders/guest', [], [], [], json_encode($orderData));
        $this->assertResponseStatusCodeSame(201);
        
        // Verify order creation
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('order', $response);
        $this->assertArrayHasKey('payment_client_secret', $response);
    }
}
```

## Performance Testing

### Load Testing with Artillery
```yaml
# artillery-config.yml
config:
  target: 'http://localhost:8080'
  phases:
    - duration: 60
      arrivalRate: 10
      name: "Warm up"
    - duration: 300
      arrivalRate: 50
      name: "Sustained load"

scenarios:
  - name: "Product browsing"
    weight: 70
    flow:
      - get:
          url: "/api/products"
      - get:
          url: "/api/products/{{ $randomInt(1, 100) }}"
  
  - name: "Guest checkout"
    weight: 30
    flow:
      - post:
          url: "/api/orders/guest"
          json:
            items: [{ product_id: "{{ $randomInt(1, 100) }}", quantity: 2 }]
            guest_email: "test{{ $randomInt(1, 1000) }}@example.com"
```

## Test Data Management
- Use Doctrine fixtures for consistent test data
- Implement database reset between tests
- Create factory classes for entity creation
- Use separate test database configuration

## Coverage Requirements
- Minimum 80% unit test coverage
- All API endpoints must have functional tests
- Critical business logic must have 100% coverage
- Include both user and guest workflows in all relevant tests

## CI/CD Integration
- Run tests on every commit
- Block deployment if tests fail
- Generate coverage reports
- Performance regression testing between phases