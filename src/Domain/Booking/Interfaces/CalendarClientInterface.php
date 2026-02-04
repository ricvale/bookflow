<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Interfaces;

use BookFlow\Domain\Booking\Booking;

interface CalendarClientInterface
{
    /**
     * @param Booking $booking
     * @param array $authData OAuth tokens and other necessary data
     * @return string The external calendar event ID
     */
    public function createEvent(Booking $booking, array $authData, string $resourceName = ''): string;

    /**
     * @param string $eventId
     * @param array $authData
     * @return void
     */
    public function cancelEvent(string $eventId, array $authData): void;

    /**
     * Check if the person is available for a given time range.
     *
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @param array $authData
     * @return bool
     */
    public function isAvailable(\DateTimeImmutable $start, \DateTimeImmutable $end, array $authData): bool;

    /**
     * Check if a specific event still exists in the external calendar.
     */
    public function eventExists(string $eventId, array $authData): bool;

    /**
     * Get event details from the external calendar.
     *
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable}|null
     */
    public function getEvent(string $eventId, array $authData): ?array;
}
