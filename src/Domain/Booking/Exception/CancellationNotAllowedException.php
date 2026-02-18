<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Exception;

/**
 * Thrown when a cancellation is not allowed by policy
 * (e.g. within the minimum hours before start).
 */
final class CancellationNotAllowedException extends BookingException
{
    public static function tooCloseToStart(int $minimumHours): self
    {
        return new self(
            sprintf(
                'Cancellation is not allowed. Bookings must be cancelled at least %d hour(s) before the start time.',
                $minimumHours
            )
        );
    }
}
