---
name: availability
description: Resource availability rules and scheduling constraints. Use when implementing booking windows, working hours, or blackout periods.
---

# Availability Skill

## Responsibilities
- Define when resources can be booked
- Enforce working hours and time zones
- Handle recurring availability patterns
- Manage blackout periods and exceptions
- Validate booking requests against availability rules

## Constraints
- All times must be timezone-aware (use `DateTimeImmutable` with timezone)
- Availability rules are tenant-scoped
- Rules can be resource-specific or global
- Must handle DST transitions correctly

## Domain Concepts

### Availability Rule Types
1. **Working Hours**: Mon-Fri 9am-5pm
2. **Recurring Schedule**: Every Tuesday 2pm-4pm
3. **Blackout Period**: Dec 24-26 (no bookings)
4. **Override**: Specific date has different hours

### Example Domain Model
```php
final class AvailabilityRule
{
    public function __construct(
        private TenantId $tenantId,
        private ?ResourceId $resourceId, // null = applies to all
        private RuleType $type,
        private DateTimeImmutable $startsAt,
        private DateTimeImmutable $endsAt,
        private RecurrencePattern $recurrence,
    ) {}
    
    public function allowsBooking(
        DateTimeImmutable $requestedStart,
        DateTimeImmutable $requestedEnd
    ): bool {
        // Check if requested time falls within available window
    }
}
```

## Implementation Pattern

### Check Availability Use Case
```php
final class CheckAvailability
{
    public function execute(CheckAvailabilityQuery $query): AvailabilityResult
    {
        $tenantId = $this->tenantContext->getTenantId();
        
        // Get all applicable rules
        $rules = $this->availabilityRules->findForResource(
            $tenantId,
            $query->resourceId,
            $query->startsAt,
            $query->endsAt
        );
        
        // Check each rule
        foreach ($rules as $rule) {
            if (!$rule->allowsBooking($query->startsAt, $query->endsAt)) {
                return AvailabilityResult::unavailable(
                    reason: "Outside working hours"
                );
            }
        }
        
        // Check for existing bookings (conflicts)
        $conflicts = $this->bookings->findOverlapping(
            $tenantId,
            $query->resourceId,
            $query->startsAt,
            $query->endsAt
        );
        
        if (!empty($conflicts)) {
            return AvailabilityResult::unavailable(
                reason: "Time slot already booked"
            );
        }
        
        return AvailabilityResult::available();
    }
}
```

## Edge Cases

### Daylight Saving Time
```php
// ✅ Correct: Use timezone-aware dates
$start = new DateTimeImmutable('2026-03-08 09:00:00', new DateTimeZone('America/New_York'));
$end = $start->modify('+1 hour'); // Correctly handles DST

// ❌ Wrong: UTC without timezone context
$start = new DateTimeImmutable('2026-03-08 09:00:00', new DateTimeZone('UTC'));
```

### Back-to-Back Bookings
```php
// Should 9:00-10:00 and 10:00-11:00 conflict?
// Decision: No, if end time equals start time, it's allowed
public function overlaps(Booking $other): bool
{
    return $this->startsAt < $other->endsAt 
        && $this->endsAt > $other->startsAt;
}
```

### Buffer Times
```php
final class AvailabilityRule
{
    private int $bufferMinutes = 15;
    
    public function allowsBooking(...): bool
    {
        // Add buffer before and after
        $bufferedStart = $requestedStart->modify("-{$this->bufferMinutes} minutes");
        $bufferedEnd = $requestedEnd->modify("+{$this->bufferMinutes} minutes");
        
        // Check against buffered times
    }
}
```

## Testing
```php
public function testRejectsBookingOutsideWorkingHours(): void
{
    $rule = new WorkingHoursRule(
        tenantId: $tenantId,
        startTime: '09:00',
        endTime: '17:00',
        timezone: 'America/New_York'
    );
    
    $result = $rule->allowsBooking(
        new DateTimeImmutable('2026-01-15 08:00:00', new DateTimeZone('America/New_York')),
        new DateTimeImmutable('2026-01-15 09:00:00', new DateTimeZone('America/New_York'))
    );
    
    $this->assertFalse($result);
}

public function testHandlesDSTTransition(): void
{
    // March 8, 2026: DST starts at 2am (clocks jump to 3am)
    $start = new DateTimeImmutable('2026-03-08 01:30:00', new DateTimeZone('America/New_York'));
    $end = $start->modify('+2 hours'); // Should be 4:30am, not 3:30am
    
    $this->assertEquals('04:30', $end->format('H:i'));
}
```

## Non-goals
- Calendar view rendering (frontend concern)
- Booking creation (separate booking skill)
- Resource management (separate resource skill)
- Pricing based on time slots (separate billing skill)

## References
- [PHP DateTimeImmutable](https://www.php.net/manual/en/class.datetimeimmutable.php)
- [IANA Time Zone Database](https://www.iana.org/time-zones)
- [Booking SKILL.md](../booking/SKILL.md)
