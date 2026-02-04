<?php

declare(strict_types=1);

namespace BookFlow\Application\Booking;

use BookFlow\Application\Shared\Interfaces\UserContextInterface;
use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\Interfaces\CalendarClientInterface;
use BookFlow\Domain\Resource\ResourceRepositoryInterface;
use BookFlow\Domain\Shared\DateRange;
use BookFlow\Domain\User\UserRepository;
use DateTimeImmutable;

/**
 * Service for synchronizing bookings with external calendars.
 */
final class CalendarSyncService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
        private ResourceRepositoryInterface $resourceRepo,
        private UserRepository $userRepo,
        private CalendarClientInterface $calendarClient,
        private UserContextInterface $userContext
    ) {
    }

    /**
     * Sync a new booking to the user's Google Calendar.
     */
    public function syncCreated(Booking $booking): void
    {
        try {
            $userId = $this->userContext->getUserId();
        } catch (\RuntimeException $e) {
            return;
        }

        $user = $this->userRepo->findById($userId);
        if (!$user || !$user->googleAuthData()) {
            return;
        }

        $resource = $this->resourceRepo->findById($booking->resourceId());
        $resourceName = $resource ? $resource->name() : 'Unknown Resource';

        $eventId = $this->calendarClient->createEvent(
            $booking,
            $user->googleAuthData(),
            $resourceName
        );

        if ($eventId) {
            $booking->setGoogleEventId($eventId);
            $this->bookingRepo->save($booking);
        }
    }

    /**
     * Remove a cancelled booking from the user's Google Calendar.
     */
    public function syncCancelled(Booking $booking): void
    {
        if (!$booking->googleEventId()) {
            return;
        }

        try {
            $userId = $this->userContext->getUserId();
        } catch (\RuntimeException $e) {
            return;
        }

        $user = $this->userRepo->findById($userId);
        if (!$user || !$user->googleAuthData()) {
            return;
        }

        $this->calendarClient->cancelEvent($booking->googleEventId(), $user->googleAuthData());
    }

    /**
     * Check if a time slot is available on the user's Google Calendar.
     */
    public function checkAvailability(DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        try {
            $userId = $this->userContext->getUserId();
        } catch (\RuntimeException $e) {
            return true;
        }

        $user = $this->userRepo->findById($userId);
        if (!$user || !$user->googleAuthData()) {
            return true;
        }

        return $this->calendarClient->isAvailable($start, $end, $user->googleAuthData());
    }

    /**
     * Reconcile a list of bookings with the external calendar.
     *
     * @param Booking[] $bookings
     */
    public function reconcile(array $bookings, ?\BookFlow\Application\Shared\Interfaces\EventDispatcherInterface $dispatcher = null): void
    {
        foreach ($bookings as $booking) {
            // If it's already on Google, check if it still exists
            if ($booking->googleEventId()) {
                try {
                    $userId = $this->userContext->getUserId();
                    $user = $this->userRepo->findById($userId);

                    if ($user && $user->googleAuthData()) {
                        $externalEvent = $this->calendarClient->getEvent($booking->googleEventId(), $user->googleAuthData());

                        if ($externalEvent === null) {
                            if ($booking->status() !== \BookFlow\Domain\Booking\BookingStatus::CANCELLED) {
                                $booking->cancel();
                                $this->bookingRepo->save($booking);
                                if ($dispatcher) {
                                    $dispatcher->dispatchAll($booking->releaseEvents());
                                }
                            }
                        } else {
                            $externalStart = $externalEvent['start'];
                            $externalEnd = $externalEvent['end'];
                            $localStart = $booking->timeSlot()->startsAt();
                            $localEnd = $booking->timeSlot()->endsAt();

                            if (
                                $externalStart->getTimestamp() !== $localStart->getTimestamp() ||
                                $externalEnd->getTimestamp() !== $localEnd->getTimestamp()
                            ) {
                                $booking->reschedule(new DateRange($externalStart, $externalEnd));
                                $this->bookingRepo->save($booking);
                                if ($dispatcher) {
                                    $dispatcher->dispatchAll($booking->releaseEvents());
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
}
