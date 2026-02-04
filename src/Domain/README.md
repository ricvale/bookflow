# Domain Layer

## Purpose
Contains **pure business logic** with zero dependencies on infrastructure, HTTP, or external services.

## Rules
✅ **Allowed:**
- Value Objects (e.g., `TenantId`, `DateRange`)
- Entities (e.g., `Booking`, `Resource`)
- Domain Services (pure business logic)
- Domain Events (e.g., `BookingCreated`)
- Repository Interfaces (contracts only, no implementation)
- Exceptions for domain invariant violations

❌ **Forbidden:**
- Database queries or SQL
- HTTP requests or responses
- External API calls
- File system operations
- Logging (use domain events instead)
- Framework dependencies
- Static time (inject `ClockInterface`)

## Testing
All domain logic must be **unit testable** without mocks for external services.

## Example
```php
// ✅ Good - Pure domain logic
final class Booking
{
    public function overlaps(Booking $other): bool
    {
        return $this->startsAt < $other->endsAt 
            && $this->endsAt > $other->startsAt;
    }
}

// ❌ Bad - Infrastructure leak
final class Booking
{
    public function save(): void
    {
        DB::table('bookings')->insert([...]); // NO!
    }
}
```

## Namespace
`BookFlow\Domain\*`
