<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Exception;

use BookFlow\Domain\Booking\BookingStatus;

/**
 * Thrown when a booking operation is invalid for the current state.
 */
final class InvalidBookingStateException extends BookingException
{
    public function __construct(
        public readonly BookingStatus $currentStatus,
        public readonly string $attemptedOperation,
        string $message = 'Invalid booking state for operation'
    ) {
        parent::__construct($message);
    }

    public static function cannotCancel(BookingStatus $status): self
    {
        return new self(
            $status,
            'cancel',
            sprintf('Cannot cancel a booking with status: %s', $status->value)
        );
    }

    public static function alreadyCancelled(): self
    {
        return new self(
            BookingStatus::CANCELLED,
            'cancel',
            'Booking is already cancelled'
        );
    }
}
