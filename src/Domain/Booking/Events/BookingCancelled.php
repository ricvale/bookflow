<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Events;

use BookFlow\Domain\Shared\TenantId;

final readonly class BookingCancelled
{
    public function __construct(
        public string $bookingId,
        public TenantId $tenantId
    ) {
    }
}
