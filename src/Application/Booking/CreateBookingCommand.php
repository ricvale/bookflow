<?php

declare(strict_types=1);

namespace BookFlow\Application\Booking;

use DateTimeImmutable;

final readonly class CreateBookingCommand
{
    public function __construct(
        public string $resourceId,
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt
    ) {
    }
}
