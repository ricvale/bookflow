<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Events;

use BookFlow\Domain\Shared\TenantId;
use DateTimeImmutable;

/**
 * Domain event raised when a booking is rescheduled.
 */
final readonly class BookingRescheduled
{
    public function __construct(
        public string $bookingId,
        public TenantId $tenantId,
        public DateTimeImmutable $oldStartsAt,
        public DateTimeImmutable $oldEndsAt,
        public DateTimeImmutable $newStartsAt,
        public DateTimeImmutable $newEndsAt
    ) {
    }
}
