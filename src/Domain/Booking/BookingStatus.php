<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking;

enum BookingStatus: string
{
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';
}
