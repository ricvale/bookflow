# Multi-Tenancy

## Strategy
- **Single database**: All tenants share one database instance
- **Shared schema**: All tables have a `tenant_id` column
- **Row-level isolation**: Every query filters by `tenant_id`

## Rules

### Security-Critical Rules
1. **tenant_id is NEVER accepted from user input**
   - Not from HTTP request body
   - Not from query parameters
   - Not from headers (except JWT payload)

2. **tenant_id is resolved from authenticated user**
   - Extracted from JWT token claims
   - Set in `TenantContext` by authentication middleware

3. **All queries MUST be tenant-scoped**
   - Every `SELECT`, `UPDATE`, `DELETE` includes `WHERE tenant_id = ?`
   - Repository layer enforces this automatically

## Implementation

### Database Schema
```sql
CREATE TABLE bookings (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    resource_id VARCHAR(36) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    
    INDEX idx_tenant_bookings (tenant_id, starts_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

### Repository Pattern
```php
final class MySqlBookingRepository implements BookingRepository
{
    public function findById(string $id): ?Booking
    {
        $tenantId = $this->tenantContext->getTenantId();
        
        $stmt = $this->pdo->prepare(
            'SELECT * FROM bookings WHERE id = ? AND tenant_id = ?'
        );
        $stmt->execute([$id, $tenantId->toString()]);
        
        return $this->hydrate($stmt->fetch());
    }
}
```

## Isolation

### Data Isolation
- **No cross-tenant reads**: Queries always filter by `tenant_id`
- **No cross-tenant writes**: Inserts always include `tenant_id`
- **Cascade deletes**: When tenant is deleted, all data is removed

### Logging Isolation
```php
$logger->info('Booking created', [
    'tenant_id' => $tenantId->toString(),
    'booking_id' => $booking->id(),
    'user_id' => $userId,
]);
```

### Testing Isolation
```php
public function testCannotAccessOtherTenantData(): void
{
    $tenantA = TenantId::fromString('tenant-a');
    $tenantB = TenantId::fromString('tenant-b');
    
    // Create booking for tenant A
    $this->tenantContext->setTenantId($tenantA);
    $booking = $this->bookings->save(new Booking(...));
    
    // Switch to tenant B
    $this->tenantContext->setTenantId($tenantB);
    
    // Should not find tenant A's booking
    $found = $this->bookings->findById($booking->id());
    $this->assertNull($found);
}
```

## Common Pitfalls

### ❌ Accepting tenant_id from request
```php
// CRITICAL VULNERABILITY!
$tenantId = $request->input('tenant_id');
```

### ❌ Missing tenant filter
```php
// SECURITY HOLE!
$stmt = $this->pdo->prepare('SELECT * FROM bookings WHERE id = ?');
```

### ❌ Trusting client-provided tenant
```php
// BYPASSABLE!
if ($request->input('tenant_id') === $user->tenant_id) {
    // Attacker can just send correct tenant_id
}
```

## See Also
- [skills/tenancy/SKILL.md](../skills/tenancy/SKILL.md) - Detailed implementation guide
- [TenantId.php](../src/Domain/Shared/TenantId.php) - Value object
- [TenantContextInterface.php](../src/Application/Shared/Interfaces/TenantContextInterface.php) - Contract
