<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Events;

use BookFlow\Domain\Shared\TenantId;
use DateTimeImmutable;

/**
 * Domain event raised when a new booking is created.
 */
final readonly class BookingCreated
{
    public function __construct(
        public string $bookingId,
        public TenantId $tenantId,
        public string $resourceId,
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt
    ) {
    }
}
