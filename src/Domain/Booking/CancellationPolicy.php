<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking;

use DateTimeImmutable;

/**
 * Policy for when a booking may be cancelled.
 *
 * Cancellation is allowed only if the booking start is at least
 * the configured number of hours in the future.
 */
final class CancellationPolicy
{
    public function __construct(
        private int $minimumHoursBeforeStart
    ) {
        if ($minimumHoursBeforeStart < 0) {
            throw new \InvalidArgumentException('Minimum hours before start must be >= 0');
        }
    }

    /**
     * Whether cancellation is allowed at the given point in time.
     */
    public function allowsCancellation(DateTimeImmutable $bookingStart, DateTimeImmutable $now): bool
    {
        if ($bookingStart <= $now) {
            return false;
        }

        $cutoff = $now->modify("+{$this->minimumHoursBeforeStart} hours");
        return $bookingStart >= $cutoff;
    }

    public function minimumHoursBeforeStart(): int
    {
        return $this->minimumHoursBeforeStart;
    }
}
