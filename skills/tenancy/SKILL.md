---
name: tenancy
description: Multi-tenant isolation and tenant context resolution. Use when implementing tenant-scoped queries, authentication, or data isolation.
---

# Tenancy Skill

## Responsibilities
- Resolve current tenant from authentication context
- Enforce tenant isolation in all data access
- Prevent cross-tenant data leakage
- Validate tenant ownership of resources

## Constraints
- `tenant_id` is **NEVER** accepted from user input (HTTP request, form data, query params)
- `tenant_id` is resolved from authenticated user's JWT token or session
- All database queries **MUST** include `WHERE tenant_id = ?`
- All repository methods **MUST** use `TenantContextInterface` to get current tenant

## Security Rules

### ✅ Correct Pattern
```php
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
        
        return $this->hydrate($stmt->fetch());
    }
}
```

### ❌ SECURITY HOLES - Never Do This
```php
// ❌ Accepting tenant_id from request
$tenantId = $_POST['tenant_id']; // CRITICAL VULNERABILITY!

// ❌ Missing tenant filter
$stmt = $this->connection->prepare('SELECT * FROM bookings WHERE id = ?');

// ❌ Trusting client-provided tenant
if ($request->input('tenant_id') === $user->tenant_id) { ... } // BYPASSABLE!
```

## Implementation Layers

### Domain Layer
- **TenantId** value object (immutable, validated)
- No tenant resolution logic (pure domain)

### Application Layer
- **TenantContextInterface** (contract for resolving tenant)
- Use cases receive tenant from context, never from input

### Infrastructure Layer
- **HttpTenantContext** (extracts from JWT)
- **InMemoryTenantContext** (for testing)
- All repository implementations enforce tenant scope

### HTTP Layer
- Middleware validates JWT and sets tenant context
- Controllers never accept `tenant_id` as input parameter

## Edge Cases
- **Tenant switching**: Users with access to multiple tenants must explicitly switch (new JWT)
- **Super admin**: May need special "view as tenant" mode with audit logging
- **Shared resources**: Some data (e.g., system settings) may be tenant-agnostic

## Testing
```php
public function testCannotAccessOtherTenantData(): void
{
    $context = new InMemoryTenantContext();
    $context->setTenantId(TenantId::fromString('tenant-a'));
    
    $repo = new MySqlBookingRepository($pdo, $context);
    
    // This booking belongs to tenant-b
    $booking = $repo->findById('booking-from-tenant-b');
    
    $this->assertNull($booking); // Must not be found
}
```

## Non-goals
- Multi-database tenancy (we use single DB, shared schema)
- Tenant creation/management (separate admin context)
- Billing per tenant (separate billing context)

## References
- [TENANCY.md](../../docs/TENANCY.md)
- [TenantId.php](../../src/Domain/Shared/TenantId.php)
- [TenantContextInterface.php](../../src/Application/Shared/Interfaces/TenantContextInterface.php)
