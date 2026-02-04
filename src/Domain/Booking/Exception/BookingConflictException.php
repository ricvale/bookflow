<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Exception;

use BookFlow\Domain\Resource\ResourceId;
use DateTimeImmutable;

/**
 * Thrown when a booking conflicts with an existing booking.
 */
final class BookingConflictException extends BookingException
{
    public function __construct(
        string $message = 'Time slot is already booked',
        public readonly ?ResourceId $resourceId = null,
        public readonly ?DateTimeImmutable $startsAt = null,
        public readonly ?DateTimeImmutable $endsAt = null
    ) {
        parent::__construct($message);
    }

    public static function forTimeSlot(
        ResourceId $resourceId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt
    ): self {
        return new self(
            sprintf(
                'Resource %s is already booked from %s to %s',
                $resourceId->toString(),
                $startsAt->format('Y-m-d H:i'),
                $endsAt->format('Y-m-d H:i')
            ),
            $resourceId,
            $startsAt,
            $endsAt
        );
    }
}
