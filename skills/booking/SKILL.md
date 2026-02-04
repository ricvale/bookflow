# Booking Skill

## Context
Handles the core scheduling logic for the system.

## Key Constraints
1. **Overlaps**: No two bookings can overlap for the same resource.
2. **Duration**: Minimum 15 minutes, Maximum 4 hours.
3. **Lead Time**: Must be booked at least 24 hours in advance.

## Data Model
- `id`: UUID
- `tenant_id`: UUID
- `starts_at`: DateTimeImmutable (UTC)
- `ends_at`: DateTimeImmutable (UTC)
- `status`: Enum (CONFIRMED, CANCELLED)
