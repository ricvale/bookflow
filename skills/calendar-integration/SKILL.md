---
name: calendar-integration
description: Google Calendar synchronization for bookings. Use when implementing two-way sync, event creation, or calendar webhooks.
---

# Calendar Integration Skill

## Responsibilities
- Sync bookings to Google Calendar
- Handle Google Calendar webhooks (event updates, deletions)
- Manage OAuth2 authentication for calendar access
- Handle sync conflicts and retries
- Maintain mapping between bookings and calendar events

## Constraints
- Calendar API calls are **infrastructure concern** (never in domain layer)
- Sync happens **asynchronously** via background jobs
- Domain emits events, listeners queue jobs
- Must handle API rate limits and failures gracefully
- Tenant-specific calendar credentials (OAuth per tenant)

## Architecture Pattern

### Domain Layer (Events Only)
```php
final class Booking
{
    public static function create(...): self
    {
        $booking = new self(...);
        
        // Emit event for side effects
        $booking->recordEvent(new BookingCreated($booking));
        
        return $booking;
    }
}
```

### Application Layer (Event Listener)
```php
final class SyncBookingToCalendar
{
    public function __construct(
        private JobQueue $queue
    ) {}
    
    public function handle(BookingCreated $event): void
    {
        // Queue background job
        $this->queue->push(new CreateCalendarEventJob(
            bookingId: $event->booking->id(),
            tenantId: $event->booking->tenantId()
        ));
    }
}
```

### Infrastructure Layer (Job Implementation)
```php
final class CreateCalendarEventJob
{
    public function handle(
        BookingRepository $bookings,
        GoogleCalendarClient $calendar,
        TenantContextInterface $tenantContext
    ): void {
        // Set tenant context
        $tenantContext->setTenantId($this->tenantId);
        
        // Load booking
        $booking = $bookings->findById($this->bookingId);
        
        // Create calendar event
        $event = $calendar->createEvent([
            'summary' => $booking->title(),
            'start' => ['dateTime' => $booking->startsAt()->format('c')],
            'end' => ['dateTime' => $booking->endsAt()->format('c')],
        ]);
        
        // Store mapping
        $this->calendarMappings->save(
            bookingId: $booking->id(),
            calendarEventId: $event['id']
        );
    }
}
```

## Google Calendar API Integration

### OAuth2 Setup
```php
final class GoogleCalendarClient
{
    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private TokenStorage $tokenStorage
    ) {}
    
    public function getAuthUrl(TenantId $tenantId): string
    {
        $client = new Google_Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri('https://bookflow.app/oauth/callback');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->setState($tenantId->toString());
        
        return $client->createAuthUrl();
    }
    
    public function handleCallback(string $code, TenantId $tenantId): void
    {
        $client = new Google_Client();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        
        // Store token for tenant
        $this->tokenStorage->save($tenantId, $token);
    }
}
```

### Event Creation
```php
public function createEvent(array $eventData): array
{
    $client = $this->getAuthenticatedClient();
    $service = new Google_Service_Calendar($client);
    
    $event = new Google_Service_Calendar_Event($eventData);
    
    try {
        $createdEvent = $service->events->insert('primary', $event);
        return $createdEvent->toSimpleObject();
    } catch (Google_Service_Exception $e) {
        // Handle rate limits, auth errors, etc.
        if ($e->getCode() === 429) {
            throw new RateLimitExceededException();
        }
        throw new CalendarSyncException($e->getMessage());
    }
}
```

## Webhook Handling

### Google Calendar Webhook
```php
final class GoogleCalendarWebhookController
{
    public function handle(Request $request): Response
    {
        // Verify webhook signature
        $this->verifyWebhookSignature($request);
        
        // Parse notification
        $channelId = $request->header('X-Goog-Channel-ID');
        $resourceState = $request->header('X-Goog-Resource-State');
        
        if ($resourceState === 'sync') {
            return $this->json(['status' => 'ok']);
        }
        
        // Queue job to fetch changes
        $this->queue->push(new FetchCalendarChangesJob($channelId));
        
        return $this->json(['status' => 'queued']);
    }
}
```

## Edge Cases

### Sync Conflicts
```php
// User updates booking in BookFlow
// Meanwhile, event is updated in Google Calendar
// Resolution: Last-write-wins or manual conflict resolution

final class ResolveCalendarConflict
{
    public function execute(ConflictData $conflict): void
    {
        // Strategy 1: BookFlow is source of truth
        $this->calendar->updateEvent($conflict->calendarEventId, [
            'start' => $conflict->booking->startsAt(),
            'end' => $conflict->booking->endsAt(),
        ]);
        
        // Strategy 2: Manual resolution (notify user)
        $this->notifications->send(new CalendarConflictNotification($conflict));
    }
}
```

### Rate Limiting
```php
final class RateLimitedCalendarClient
{
    private const MAX_REQUESTS_PER_MINUTE = 60;
    
    public function createEvent(array $data): array
    {
        if ($this->rateLimiter->tooManyAttempts()) {
            // Retry later
            throw new RateLimitExceededException(
                retryAfter: $this->rateLimiter->availableIn()
            );
        }
        
        $this->rateLimiter->hit();
        
        return $this->client->createEvent($data);
    }
}
```

### Token Refresh
```php
public function getAuthenticatedClient(TenantId $tenantId): Google_Client
{
    $token = $this->tokenStorage->get($tenantId);
    
    $client = new Google_Client();
    $client->setAccessToken($token);
    
    // Refresh if expired
    if ($client->isAccessTokenExpired()) {
        $newToken = $client->fetchAccessTokenWithRefreshToken();
        $this->tokenStorage->save($tenantId, $newToken);
    }
    
    return $client;
}
```

## Testing
```php
public function testCreatesCalendarEventWhenBookingCreated(): void
{
    $calendar = $this->createMock(GoogleCalendarClient::class);
    $calendar->expects($this->once())
        ->method('createEvent')
        ->with($this->callback(function ($data) {
            return $data['summary'] === 'Test Booking';
        }));
    
    $job = new CreateCalendarEventJob($bookingId, $tenantId);
    $job->handle($bookings, $calendar, $tenantContext);
}
```

## Non-goals
- Supporting multiple calendar providers (only Google for now)
- Calendar view rendering (frontend concern)
- Recurring event management (complex, future enhancement)
- Attendee management (separate feature)

## References
- [Google Calendar API](https://developers.google.com/calendar/api/v3/reference)
- [OAuth2 for Web Server Applications](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Webhook Push Notifications](https://developers.google.com/calendar/api/guides/push)
