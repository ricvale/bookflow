<?php

declare(strict_types=1);

namespace BookFlow\Application\Booking;

use BookFlow\Application\Shared\Interfaces\EventDispatcherInterface;
use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Domain\Booking\BookingId;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\Exception\BookingNotFoundException;

/**
 * Use case for cancelling an existing booking.
 */
final class CancelBooking
{
    public function __construct(
        private BookingRepositoryInterface $bookings,
        private TenantContextInterface $tenantContext,
        private ?EventDispatcherInterface $eventDispatcher = null
    ) {
    }

    /**
     * Execute the booking cancellation.
     *
     * @throws BookingNotFoundException If the booking doesn't exist
     */
    public function execute(string $bookingId): void
    {
        $id = BookingId::fromString($bookingId);
        $booking = $this->bookings->findByBookingId($id);

        if ($booking === null) {
            throw BookingNotFoundException::withId($id);
        }

        // Verify tenant ownership
        if (!$booking->tenantId()->equals($this->tenantContext->getTenantId())) {
            throw BookingNotFoundException::withId($id); // Don't reveal existence
        }

        $booking->cancel();

        $this->bookings->save($booking);

        // Dispatch domain events (including BookingCancelled)
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatchAll($booking->releaseEvents());
        }
    }
}
