# Testing Guide

## Overview
BookFlow uses **PHPUnit 10** for testing with a focus on **high test coverage** and **fast feedback loops**.

## Test Structure

```
tests/
├── Unit/              # Fast, isolated tests (no DB, no HTTP)
│   ├── Domain/       # Pure business logic tests
│   └── Application/  # Use case tests (with mocks)
└── Integration/       # Tests with real dependencies
    └── Infrastructure/ # Database, API tests
```

## Running Tests

### All Tests
```bash
docker compose exec app ./vendor/bin/phpunit
```

### Unit Tests Only (Fast)
```bash
docker compose exec app ./vendor/bin/phpunit --testsuite=Unit
```

### Integration Tests Only
```bash
docker compose exec app ./vendor/bin/phpunit --testsuite=Integration
```

### With Coverage
```bash
docker compose exec app ./vendor/bin/phpunit --coverage-html coverage
```

### Single Test File
```bash
docker compose exec app ./vendor/bin/phpunit tests/Unit/Domain/Shared/TenantIdTest.php
```

### Single Test Method
```bash
docker compose exec app ./vendor/bin/phpunit --filter testRejectsEmptyString
```

## Testing Principles

### 1. Unit Tests (Domain & Application)
**Goal**: Test business logic in isolation

**Characteristics**:
- No database
- No HTTP
- No external services
- Fast (milliseconds)
- Use mocks for dependencies

**Example**:
```php
final class BookingTest extends TestCase
{
    public function testDetectsOverlappingBookings(): void
    {
        $booking1 = new Booking(
            startsAt: new DateTimeImmutable('2026-01-15 09:00'),
            endsAt: new DateTimeImmutable('2026-01-15 10:00')
        );
        
        $booking2 = new Booking(
            startsAt: new DateTimeImmutable('2026-01-15 09:30'),
            endsAt: new DateTimeImmutable('2026-01-15 10:30')
        );
        
        $this->assertTrue($booking1->overlaps($booking2));
    }
}
```

### 2. Integration Tests (Infrastructure)
**Goal**: Test real database interactions, API calls

**Characteristics**:
- Use real database (test DB)
- Test repository implementations
- Slower (seconds)
- Clean up after each test

**Example**:
```php
final class MySqlBookingRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MySqlBookingRepository $repository;
    
    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=db;dbname=bookflow_test');
        $this->pdo->exec('TRUNCATE TABLE bookings');
        
        $tenantContext = new InMemoryTenantContext();
        $tenantContext->setTenantId(TenantId::fromString('test-tenant'));
        
        $this->repository = new MySqlBookingRepository($this->pdo, $tenantContext);
    }
    
    public function testCanSaveAndRetrieveBooking(): void
    {
        $booking = new Booking(...);
        
        $this->repository->save($booking);
        $found = $this->repository->findById($booking->id());
        
        $this->assertEquals($booking->id(), $found->id());
    }
}
```

## Test Data Builders

Use **builders** for complex objects:

```php
final class BookingBuilder
{
    private string $id = 'booking-123';
    private TenantId $tenantId;
    private DateTimeImmutable $startsAt;
    private DateTimeImmutable $endsAt;
    
    public function __construct()
    {
        $this->tenantId = TenantId::fromString('tenant-123');
        $this->startsAt = new DateTimeImmutable('2026-01-15 09:00');
        $this->endsAt = new DateTimeImmutable('2026-01-15 10:00');
    }
    
    public function withId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    public function withTenant(TenantId $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }
    
    public function build(): Booking
    {
        return new Booking(
            id: $this->id,
            tenantId: $this->tenantId,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt
        );
    }
}

// Usage
$booking = (new BookingBuilder())
    ->withId('custom-id')
    ->withTenant(TenantId::fromString('tenant-456'))
    ->build();
```

## Testing Multi-Tenancy

**Critical**: Always test tenant isolation!

```php
public function testCannotAccessOtherTenantBookings(): void
{
    $tenantA = TenantId::fromString('tenant-a');
    $tenantB = TenantId::fromString('tenant-b');
    
    // Create booking for tenant A
    $this->tenantContext->setTenantId($tenantA);
    $bookingA = $this->repository->save(new Booking(...));
    
    // Switch to tenant B
    $this->tenantContext->setTenantId($tenantB);
    
    // Should NOT find tenant A's booking
    $found = $this->repository->findById($bookingA->id());
    $this->assertNull($found);
}
```

## Testing Time-Dependent Logic

**Never use `new DateTime()`** in tests! Inject a clock.

```php
interface ClockInterface
{
    public function now(): DateTimeImmutable;
}

final class FixedClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $fixedTime) {}
    
    public function now(): DateTimeImmutable
    {
        return $this->fixedTime;
    }
}

// In test
$clock = new FixedClock(new DateTimeImmutable('2026-01-15 12:00:00'));
$service = new BookingService($repository, $clock);
```

## Mocking Best Practices

### Use PHPUnit Mocks
```php
$repository = $this->createMock(BookingRepository::class);
$repository->expects($this->once())
    ->method('save')
    ->with($this->isInstanceOf(Booking::class));
```

### Avoid Over-Mocking
```php
// ❌ Bad: Mocking value objects
$tenantId = $this->createMock(TenantId::class);

// ✅ Good: Use real value objects
$tenantId = TenantId::fromString('tenant-123');
```

## Test Coverage Goals

- **Domain Layer**: 100% (pure logic, easy to test)
- **Application Layer**: 90%+ (use cases)
- **Infrastructure Layer**: 80%+ (integration tests)
- **HTTP Layer**: 70%+ (controller tests)

## Continuous Integration

Tests run automatically on every push via GitHub Actions:
```bash
# See .github/workflows/ci.yml
composer install
./vendor/bin/phpunit --coverage-text
```

## Common Patterns

### Testing Exceptions
```php
public function testThrowsWhenBookingConflicts(): void
{
    $this->expectException(BookingConflictException::class);
    $this->expectExceptionMessage('Time slot already booked');
    
    $service->createBooking($command);
}
```

### Testing Domain Events
```php
public function testEmitsBookingCreatedEvent(): void
{
    $booking = Booking::create(...);
    
    $events = $booking->releaseEvents();
    
    $this->assertCount(1, $events);
    $this->assertInstanceOf(BookingCreated::class, $events[0]);
}
```

## Performance Tips

1. **Use in-memory SQLite for integration tests** (faster than MySQL)
2. **Run unit tests first** (fail fast)
3. **Parallelize tests** (PHPUnit 10 supports this)
4. **Use database transactions** (rollback after each test)

## See Also
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Test-Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)
