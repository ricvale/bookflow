---
name: booking
description: Booking creation and conflict resolution
---

# Booking Skill

## Responsibilities
- Create, update, cancel bookings
- Prevent overlapping bookings
- Apply availability rules
- Emit domain events for side effects (notifications, calendar sync)

## Constraints
- Bookings must not overlap for the same resource
- All times must be timezone-safe
- Cancelled bookings remain in history
- tenant_id must be enforced on all queries

## Edge Cases
- Daylight Saving Time transitions
- Back-to-back bookings
- Buffer times
- Concurrent booking attempts

## Non-goals
- Payments (handled by separate billing context)
- Direct notifications (use domain events → background jobs)
- Calendar API calls in domain layer (infrastructure concern)

## Architecture Notes
- **Domain Layer**: `Booking` entity, `BookingRepository` interface, conflict detection logic
- **Application Layer**: `CreateBooking` use case, authorization policies
- **Infrastructure Layer**: MySQL repository implementation, Google Calendar sync listener
