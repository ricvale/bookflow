<?php

declare(strict_types=1);

namespace BookFlow\Tests\Unit\Application\Booking;

use BookFlow\Application\Booking\CalendarSyncService;
use BookFlow\Application\Shared\Interfaces\UserContextInterface;
use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\BookingId;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\Interfaces\CalendarClientInterface;
use BookFlow\Domain\Resource\Resource;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Resource\ResourceRepositoryInterface;
use BookFlow\Domain\Shared\DateRange;
use BookFlow\Domain\Shared\TenantId;
use BookFlow\Domain\User\User;
use BookFlow\Domain\User\UserId;
use BookFlow\Domain\User\UserRepository;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CalendarSyncServiceTest extends TestCase
{
    private CalendarSyncService $service;
    private BookingRepositoryInterface&MockObject $bookingRepo;
    private ResourceRepositoryInterface&MockObject $resourceRepo;
    private UserRepository&MockObject $userRepo;
    private CalendarClientInterface&MockObject $calendarClient;
    private UserContextInterface&MockObject $userContext;

    protected function setUp(): void
    {
        $this->bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $this->resourceRepo = $this->createMock(ResourceRepositoryInterface::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->calendarClient = $this->createMock(CalendarClientInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);

        $this->service = new CalendarSyncService(
            $this->bookingRepo,
            $this->resourceRepo,
            $this->userRepo,
            $this->calendarClient,
            $this->userContext
        );
    }

    public function testSyncCreatedDoesNothingWhenNoUserContext(): void
    {
        $this->userContext->expects($this->once())
            ->method('getUserId')
            ->willThrowException(new \RuntimeException('No user'));

        $booking = $this->createBooking();

        $this->service->syncCreated($booking);

        $this->calendarClient->expects($this->never())->method('createEvent');
    }

    public function testSyncCreatedDoesNothingWhenUserNoGoogleAuth(): void
    {
        $userId = UserId::fromString('user-123');
        $this->userContext->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $user = $this->getConnectionMockUser(false);
        $this->userRepo->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $booking = $this->createBooking();

        $this->service->syncCreated($booking);

        $this->calendarClient->expects($this->never())->method('createEvent');
    }

    public function testSyncCreatedCreatesEventAndUpdatesBooking(): void
    {
        $userId = UserId::fromString('user-123');
        $this->userContext->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $user = $this->getConnectionMockUser(true);
        $this->userRepo->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $booking = $this->createBooking();
        $resource = new Resource(
            ResourceId::fromString('resource-1'),
            TenantId::fromString('tenant-1'),
            'Test Resource',
            'Description'
        );

        $this->resourceRepo->expects($this->once())
            ->method('findById')
            ->with($booking->resourceId())
            ->willReturn($resource);

        $this->calendarClient->expects($this->once())
            ->method('createEvent')
            ->willReturn('google-event-id-123');

        $this->bookingRepo->expects($this->once())
            ->method('save')
            ->with($booking);

        $this->service->syncCreated($booking);

        $this->assertEquals('google-event-id-123', $booking->googleEventId());
    }

    private function createBooking(): Booking
    {
        return Booking::create(
            BookingId::fromString('booking-123'),
            TenantId::fromString('tenant-1'),
            ResourceId::fromString('resource-1'),
            new DateRange(
                new DateTimeImmutable('2026-01-01 10:00'),
                new DateTimeImmutable('2026-01-01 11:00')
            )
        );
    }

    private function getConnectionMockUser(bool $hasAuth): User
    {
        return new User(
            UserId::fromString('user-123'),
            TenantId::fromString('tenant-1'),
            'test@example.com',
            'hash',
            'Test User',
            $hasAuth ? ['token' => 'abc'] : null
        );
    }
}
