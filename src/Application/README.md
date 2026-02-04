# Application Layer

## Purpose
Orchestrates **use cases** by coordinating domain objects and applying policies.

## Rules
✅ **Allowed:**
- Use Case classes (e.g., `CreateBooking`, `CancelBooking`)
- Authorization policies (e.g., `CanUserCancelBooking`)
- Domain event handlers/listeners
- Calling domain methods
- Calling repository interfaces
- Transaction management (via interfaces)

❌ **Forbidden:**
- Direct database access (use repositories)
- HTTP concerns (request/response objects)
- Business logic (belongs in Domain)
- External API calls (use Infrastructure services via interfaces)

## Pattern
```php
final class CreateBooking
{
    public function __construct(
        private BookingRepository $bookings,
        private TenantContextInterface $tenantContext,
        private EventDispatcher $events
    ) {}

    public function execute(CreateBookingCommand $command): Booking
    {
        $tenantId = $this->tenantContext->getTenantId();
        
        // Check for conflicts (domain logic)
        $conflicts = $this->bookings->findOverlapping(...);
        if (!empty($conflicts)) {
            throw new BookingConflictException();
        }
        
        // Create booking (domain logic)
        $booking = Booking::create(...);
        
        // Persist
        $this->bookings->save($booking);
        
        // Emit event for side effects
        $this->events->dispatch(new BookingCreated($booking));
        
        return $booking;
    }
}
```

## Namespace
`BookFlow\Application\*`
