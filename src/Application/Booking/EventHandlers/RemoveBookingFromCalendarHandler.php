<?php

declare(strict_types=1);

namespace BookFlow\Application\Booking\EventHandlers;

use BookFlow\Application\Booking\CalendarSyncService;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\Events\BookingCancelled;

/**
 * Event handler that removes cancelled bookings from Google Calendar.
 */
final class RemoveBookingFromCalendarHandler
{
    public function __construct(
        private CalendarSyncService $calendarSync,
        private BookingRepositoryInterface $bookingRepo
    ) {
    }

    public function __invoke(BookingCancelled $event): void
    {
        $booking = $this->bookingRepo->findById($event->bookingId);

        if ($booking === null) {
            return;
        }

        $this->calendarSync->syncCancelled($booking);
    }
}
