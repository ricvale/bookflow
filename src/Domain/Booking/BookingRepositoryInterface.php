<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking;

use DateTimeImmutable;

/**
 * Repository interface for Booking aggregate.
 */
interface BookingRepositoryInterface
{
    /**
     * Save a booking (insert or update).
     */
    public function save(Booking $booking): void;

    /**
     * Find a booking by its ID.
     */
    public function findById(string $id): ?Booking;

    /**
     * Find a booking by its typed ID.
     */
    public function findByBookingId(BookingId $id): ?Booking;

    /**
     * Find all bookings that conflict with a given time slot for a resource.
     *
     * @return Booking[]
     */
    public function findConflicting(string $resourceId, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): array;

    /**
     * Find all bookings for current tenant.
     *
     * @return Booking[]
     */
    public function findAll(): array;
}
