<?php

declare(strict_types=1);

namespace BookFlow\Application\Booking\EventHandlers;

use BookFlow\Application\Booking\CalendarSyncService;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\Events\BookingCreated;

/**
 * Event handler that syncs new bookings to Google Calendar.
 */
final class SyncBookingToCalendarHandler
{
    public function __construct(
        private CalendarSyncService $calendarSync,
        private BookingRepositoryInterface $bookingRepo
    ) {
    }

    public function __invoke(BookingCreated $event): void
    {
        $booking = $this->bookingRepo->findById($event->bookingId);

        if ($booking === null) {
            // Booking was deleted before handler ran (edge case)
            return;
        }

        $this->calendarSync->syncCreated($booking);
    }
}
