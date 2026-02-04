<?php

declare(strict_types=1);

namespace BookFlow\Application\Booking;

use BookFlow\Application\Shared\Interfaces\EventDispatcherInterface;
use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\BookingId;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\Exception\BookingConflictException;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Shared\DateRange;

/**
 * Use case for creating a new booking.
 */
final class CreateBooking
{
    public function __construct(
        private BookingRepositoryInterface $bookings,
        private TenantContextInterface $tenantContext,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private ?CalendarSyncService $calendarSync = null
    ) {
    }

    /**
     * Execute the booking creation.
     *
     * @throws BookingConflictException If the time slot is already booked
     */
    public function execute(CreateBookingCommand $command): Booking
    {
        $tenantId = $this->tenantContext->getTenantId();
        $resourceId = ResourceId::fromString($command->resourceId);

        // 1. Check for internal conflicts (database)
        $conflicting = $this->bookings->findConflicting(
            $command->resourceId,
            $command->startsAt,
            $command->endsAt
        );

        if (count($conflicting) > 0) {
            throw BookingConflictException::forTimeSlot(
                $resourceId,
                $command->startsAt,
                $command->endsAt
            );
        }

        // 2. Check for external conflicts (Google Calendar)
        if ($this->calendarSync !== null) {
            if (!$this->calendarSync->checkAvailability($command->startsAt, $command->endsAt)) {
                throw new BookingConflictException(
                    'This time slot is marked as busy on your Google Calendar.'
                );
            }
        }

        $timeSlot = new DateRange($command->startsAt, $command->endsAt);

        $booking = Booking::create(
            id: BookingId::generate(),
            tenantId: $tenantId,
            resourceId: $resourceId,
            timeSlot: $timeSlot
        );

        $this->bookings->save($booking);

        // Dispatch domain events
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatchAll($booking->releaseEvents());
        }

        return $booking;
    }
}
