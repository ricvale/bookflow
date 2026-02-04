<?php

declare(strict_types=1);

namespace BookFlow\Http\Controllers;

use BookFlow\Application\Booking\CalendarSyncService;
use BookFlow\Application\Booking\CancelBooking;
use BookFlow\Application\Booking\CreateBooking;
use BookFlow\Application\Booking\CreateBookingCommand;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Http\Request\CreateBookingRequest;

/**
 * HTTP Controller for booking operations.
 */
final class BookingController
{
    public function __construct(
        private CreateBooking $createBooking,
        private CancelBooking $cancelBooking,
        private BookingRepositoryInterface $bookings,
        private CalendarSyncService $calendarSync,
        private ?\BookFlow\Application\Shared\Interfaces\EventDispatcherInterface $eventDispatcher = null
    ) {
    }

    /**
     * Trigger a manual reconciliation with external calendar.
     */
    public function syncWithExternalCalendar(): array
    {
        $bookings = $this->bookings->findAll();
        $this->calendarSync->reconcile($bookings, $this->eventDispatcher);

        // Re-fetch to get updated statuses
        $updatedBookings = $this->bookings->findAll();

        return [
            'message' => 'Sync completed. Local bookings updated.',
            'bookings' => array_map(fn ($b) => [
                'id' => $b->id()->toString(),
                'resource_id' => $b->resourceId()->toString(),
                'starts_at' => $b->timeSlot()->startsAt()->format('c'),
                'ends_at' => $b->timeSlot()->endsAt()->format('c'),
                'status' => $b->status()->value,
                'created_at' => $b->createdAt()->format('c'),
            ], $updatedBookings),
        ];
    }

    /**
     * List all bookings for the current tenant.
     */
    public function index(): array
    {
        $bookings = $this->bookings->findAll();

        return [
            'bookings' => array_map(fn ($b) => [
                'id' => $b->id()->toString(),
                'resource_id' => $b->resourceId()->toString(),
                'starts_at' => $b->timeSlot()->startsAt()->format('c'),
                'ends_at' => $b->timeSlot()->endsAt()->format('c'),
                'status' => $b->status()->value,
                'created_at' => $b->createdAt()->format('c'),
            ], $bookings),
        ];
    }

    /**
     * Create a new booking.
     */
    public function store(array $data): array
    {
        // Validate request using DTO
        $request = CreateBookingRequest::fromArray($data);

        $command = new CreateBookingCommand(
            resourceId: $request->resourceId,
            startsAt: $request->startsAt,
            endsAt: $request->endsAt
        );

        $booking = $this->createBooking->execute($command);

        return [
            'booking' => [
                'id' => $booking->id()->toString(),
                'resource_id' => $booking->resourceId()->toString(),
                'starts_at' => $booking->timeSlot()->startsAt()->format('c'),
                'ends_at' => $booking->timeSlot()->endsAt()->format('c'),
                'status' => $booking->status()->value,
            ],
        ];
    }

    /**
     * Cancel a booking.
     */
    public function destroy(string $id): array
    {
        $this->cancelBooking->execute($id);

        return ['message' => 'Booking cancelled'];
    }
}
