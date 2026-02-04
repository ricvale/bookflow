# Infrastructure Layer

## Purpose
Implements **technical concerns** and external integrations.

## Rules
✅ **Allowed:**
- Repository implementations (MySQL, Redis, etc.)
- External API clients (Google Calendar, Stripe, etc.)
- Email sending
- File system operations
- Logging
- Queue/Job implementations
- Database migrations
- Third-party library integrations

❌ **Forbidden:**
- Business logic (belongs in Domain)
- Use case orchestration (belongs in Application)
- HTTP routing (belongs in Http layer)

## Pattern
```php
// Implements domain interface
final class MySqlBookingRepository implements BookingRepository
{
    public function __construct(
        private PDO $connection,
        private TenantContextInterface $tenantContext
    ) {}

    public function findById(string $id): ?Booking
    {
        $tenantId = $this->tenantContext->getTenantId();
        
        $stmt = $this->connection->prepare(
            'SELECT * FROM bookings WHERE id = ? AND tenant_id = ?'
        );
        $stmt->execute([$id, $tenantId->toString()]);
        
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        
        return $this->hydrate($row);
    }
    
    private function hydrate(array $row): Booking
    {
        // Map database row to domain entity
    }
}
```

## Multi-Tenancy
**CRITICAL:** All queries MUST be tenant-scoped. Never accept `tenant_id` from user input.

```php
// ✅ Good
$tenantId = $this->tenantContext->getTenantId();
$stmt->execute([$id, $tenantId->toString()]);

// ❌ Bad
$stmt->execute([$id, $_POST['tenant_id']]); // SECURITY HOLE!
```

## Namespace
`BookFlow\Infrastructure\*`
