<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Exception;

use BookFlow\Domain\Booking\BookingId;

/**
 * Thrown when a booking cannot be found.
 */
final class BookingNotFoundException extends BookingException
{
    public function __construct(
        public readonly BookingId $bookingId,
        string $message = 'Booking not found'
    ) {
        parent::__construct($message);
    }

    public static function withId(BookingId $bookingId): self
    {
        return new self(
            $bookingId,
            sprintf('Booking with ID %s not found', $bookingId->toString())
        );
    }
}
