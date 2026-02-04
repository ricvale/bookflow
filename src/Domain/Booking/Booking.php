<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking;

use BookFlow\Domain\Booking\Exception\InvalidBookingStateException;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Shared\DateRange;
use BookFlow\Domain\Shared\TenantId;
use DateTimeImmutable;

/**
 * Booking aggregate root.
 *
 * Represents a reservation of a resource for a specific time slot.
 */
final class Booking
{
    private array $events = [];

    private function __construct(
        private BookingId $id,
        private TenantId $tenantId,
        private ResourceId $resourceId,
        private DateRange $timeSlot,
        private BookingStatus $status,
        private DateTimeImmutable $createdAt,
        private ?string $googleEventId = null
    ) {
    }

    /**
     * Create a new booking.
     *
     * This is the only way to create a NEW booking (not from persistence).
     * Records a BookingCreated domain event.
     */
    public static function create(
        BookingId $id,
        TenantId $tenantId,
        ResourceId $resourceId,
        DateRange $timeSlot
    ): self {
        $booking = new self(
            id: $id,
            tenantId: $tenantId,
            resourceId: $resourceId,
            timeSlot: $timeSlot,
            status: BookingStatus::CONFIRMED,
            createdAt: new DateTimeImmutable()
        );

        $booking->recordEvent(new Events\BookingCreated(
            bookingId: $id->toString(),
            tenantId: $tenantId,
            resourceId: $resourceId->toString(),
            startsAt: $timeSlot->startsAt(),
            endsAt: $timeSlot->endsAt()
        ));

        return $booking;
    }

    /**
     * Reconstitute a booking from persistence.
     *
     * Does NOT record domain events - this is for loading existing bookings.
     */
    public static function reconstitute(
        BookingId $id,
        TenantId $tenantId,
        ResourceId $resourceId,
        DateRange $timeSlot,
        BookingStatus $status,
        DateTimeImmutable $createdAt,
        ?string $googleEventId = null
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            resourceId: $resourceId,
            timeSlot: $timeSlot,
            status: $status,
            createdAt: $createdAt,
            googleEventId: $googleEventId
        );
    }

    /**
     * Reschedule this booking.
     */
    public function reschedule(DateRange $newTimeSlot): void
    {
        if ($this->status === BookingStatus::CANCELLED) {
            throw InvalidBookingStateException::alreadyCancelled();
        }

        $oldTimeSlot = $this->timeSlot;
        $this->timeSlot = $newTimeSlot;

        $this->recordEvent(new Events\BookingRescheduled(
            bookingId: $this->id->toString(),
            tenantId: $this->tenantId,
            oldStartsAt: $oldTimeSlot->startsAt(),
            oldEndsAt: $oldTimeSlot->endsAt(),
            newStartsAt: $newTimeSlot->startsAt(),
            newEndsAt: $newTimeSlot->endsAt()
        ));
    }

    /**
     * Cancel this booking.
     *
     * @throws InvalidBookingStateException If already cancelled
     */
    public function cancel(): void
    {
        if ($this->status === BookingStatus::CANCELLED) {
            throw InvalidBookingStateException::alreadyCancelled();
        }

        $this->status = BookingStatus::CANCELLED;

        $this->recordEvent(new Events\BookingCancelled(
            bookingId: $this->id->toString(),
            tenantId: $this->tenantId
        ));
    }

    /**
     * Check if this booking overlaps with another.
     */
    public function overlaps(self $other): bool
    {
        // Only check active bookings
        if ($this->status === BookingStatus::CANCELLED || $other->status === BookingStatus::CANCELLED) {
            return false;
        }

        // Only check same resource
        if (!$this->resourceId->equals($other->resourceId)) {
            return false;
        }

        return $this->timeSlot->overlaps($other->timeSlot);
    }

    public function id(): BookingId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function resourceId(): ResourceId
    {
        return $this->resourceId;
    }

    public function timeSlot(): DateRange
    {
        return $this->timeSlot;
    }

    public function status(): BookingStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function googleEventId(): ?string
    {
        return $this->googleEventId;
    }

    public function setGoogleEventId(string $googleEventId): void
    {
        $this->googleEventId = $googleEventId;
    }

    /**
     * Release and return all recorded domain events.
     *
     * @return object[]
     */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->events[] = $event;
    }
}
