# HTTP Layer

## Purpose
Handles **web requests** and delegates to the Application layer.

## Rules
✅ **Allowed:**
- Controllers (thin, delegation only)
- Request validation
- Response formatting (JSON, HTML)
- Authentication & tenant resolution
- Routing
- Middleware (auth, CORS, rate limiting)

❌ **Forbidden:**
- Business logic (belongs in Domain)
- Use case orchestration (belongs in Application)
- Direct database access (use Application services)
- Domain event emission (Application layer handles this)

## Pattern
```php
final class BookingController
{
    public function __construct(
        private CreateBooking $createBooking,
        private TenantContextInterface $tenantContext
    ) {}

    public function create(Request $request): Response
    {
        // 1. Validate input
        $validated = $this->validate($request, [
            'resource_id' => 'required|uuid',
            'starts_at' => 'required|datetime',
            'ends_at' => 'required|datetime',
        ]);
        
        // 2. Build command (NO tenant_id from input!)
        $command = new CreateBookingCommand(
            resourceId: $validated['resource_id'],
            startsAt: new DateTimeImmutable($validated['starts_at']),
            endsAt: new DateTimeImmutable($validated['ends_at'])
        );
        
        // 3. Delegate to Application layer
        try {
            $booking = $this->createBooking->execute($command);
            return $this->json($booking, 201);
        } catch (BookingConflictException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }
    }
}
```

## Tenant Resolution
The HTTP layer is responsible for resolving the tenant from the request:

```php
final class HttpTenantContext implements TenantContextInterface
{
    public function __construct(private Request $request) {}
    
    public function getTenantId(): TenantId
    {
        // Extract from JWT, session, subdomain, etc.
        $token = $this->request->bearerToken();
        $payload = JWT::decode($token);
        
        return TenantId::fromString($payload['tenant_id']);
    }
}
```

## Namespace
`BookFlow\Http\*`
