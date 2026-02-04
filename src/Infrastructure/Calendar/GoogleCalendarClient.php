<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Calendar;

use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\Interfaces\CalendarClientInterface;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

final class GoogleCalendarClient implements CalendarClientInterface
{
    private Client $client;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ) {
        $this->client = new Client();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->addScope(Calendar::CALENDAR_EVENTS);
        $this->client->addScope(Calendar::CALENDAR_FREEBUSY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function createEvent(Booking $booking, array $authData, string $resourceName = ''): string
    {
        $this->authenticate($authData);
        $service = new Calendar($this->client);

        $summary = $resourceName
            ? "Booking: {$resourceName}"
            : 'Booking: ' . $booking->id()->toString();

        $event = new Event([
            'summary' => $summary,
            'description' => "Resource: {$resourceName} (ID: " . $booking->resourceId()->toString() . ')',
            'start' => new EventDateTime([
                'dateTime' => $booking->timeSlot()->startsAt()->format(\DateTime::RFC3339),
                'timeZone' => 'UTC',
            ]),
            'end' => new EventDateTime([
                'dateTime' => $booking->timeSlot()->endsAt()->format(\DateTime::RFC3339),
                'timeZone' => 'UTC',
            ]),
        ]);

        $calendarId = 'primary';
        $createdEvent = $service->events->insert($calendarId, $event);

        return $createdEvent->getId();
    }

    public function cancelEvent(string $eventId, array $authData): void
    {
        $this->authenticate($authData);
        $service = new Calendar($this->client);

        try {
            $service->events->delete('primary', $eventId);
        } catch (\Exception $e) {
            // Log error or ignore if already deleted
        }
    }

    public function isAvailable(\DateTimeImmutable $start, \DateTimeImmutable $end, array $authData): bool
    {
        $this->authenticate($authData);
        $service = new Calendar($this->client);

        $request = new \Google\Service\Calendar\FreeBusyRequest();
        $request->setTimeMin($start->format(\DateTime::RFC3339));
        $request->setTimeMax($end->format(\DateTime::RFC3339));
        $request->setItems([['id' => 'primary']]);

        $query = $service->freebusy->query($request);
        $busySlots = $query->getCalendars()['primary']->getBusy();

        // If there are any busy slots in this range, it's not available
        return count($busySlots) === 0;
    }

    public function eventExists(string $eventId, array $authData): bool
    {
        return $this->getEvent($eventId, $authData) !== null;
    }

    public function getEvent(string $eventId, array $authData): ?array
    {
        $this->authenticate($authData);
        $service = new Calendar($this->client);

        try {
            $event = $service->events->get('primary', $eventId);

            if ($event->getStatus() === 'cancelled') {
                return null;
            }

            $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
            $end = $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate();

            return [
                'start' => new \DateTimeImmutable($start),
                'end' => new \DateTimeImmutable($end),
            ];
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404 || $e->getCode() === 410) {
                return null;
            }
            throw $e;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function fetchAccessTokenWithAuthCode(string $code): array
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    private function authenticate(array $authData): void
    {
        $this->client->setAccessToken($authData);

        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            }
        }
    }
}
