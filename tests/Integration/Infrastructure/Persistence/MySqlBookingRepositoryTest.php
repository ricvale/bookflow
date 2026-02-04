<?php

declare(strict_types=1);

namespace BookFlow\Tests\Integration\Infrastructure\Persistence;

use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\BookingId;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Shared\DateRange;
use BookFlow\Domain\Shared\TenantId;
use BookFlow\Infrastructure\Persistence\MySqlBookingRepository;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

final class MySqlBookingRepositoryTest extends TestCase
{
    private PDO $pdo;
    private MySqlBookingRepository $repository;
    private TenantId $tenantId;

    protected function setUp(): void
    {
        // Use credentials from docker-compose.yml / .env.example
        $this->pdo = new PDO(
            'mysql:host=db;dbname=bookflow;charset=utf8mb4',
            'bookflow',
            'bookflow',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $this->tenantId = TenantId::fromString('tenant-test-123');

        $tenantContext = $this->createMock(TenantContextInterface::class);
        $tenantContext->method('getTenantId')->willReturn($this->tenantId);

        $this->repository = new MySqlBookingRepository($this->pdo, $tenantContext);

        // Clean up database before each test
        $this->pdo->exec('TRUNCATE TABLE bookings');
    }

    public function testSaveAndFindBooking(): void
    {
        $bookingId = BookingId::fromString('booking-1');
        $resourceId = ResourceId::fromString('resource-1');
        $timeSlot = new DateRange(
            new DateTimeImmutable('2026-02-01 10:00:00'),
            new DateTimeImmutable('2026-02-01 11:00:00')
        );

        $booking = Booking::create(
            $bookingId,
            $this->tenantId,
            $resourceId,
            $timeSlot
        );

        $this->repository->save($booking);

        $found = $this->repository->findById($bookingId->toString());

        $this->assertNotNull($found);
        $this->assertEquals($bookingId->toString(), $found->id()->toString());
        $this->assertEquals($this->tenantId->toString(), $found->tenantId()->toString());
        $this->assertEquals($resourceId->toString(), $found->resourceId()->toString());
        // Check formatted dates roughly (database may lose precision depending on setup, but DateTimeImmutable matches typically)
        $this->assertEquals($timeSlot->startsAt()->getTimestamp(), $found->timeSlot()->startsAt()->getTimestamp());
    }

    public function testFindConflictingBookings(): void
    {
        $resourceId = ResourceId::fromString('resource-1');

        // 1. Create a booking 10:00-11:00
        $booking1 = Booking::create(
            BookingId::fromString('b1'),
            $this->tenantId,
            $resourceId,
            new DateRange(
                new DateTimeImmutable('2026-02-01 10:00:00'),
                new DateTimeImmutable('2026-02-01 11:00:00')
            )
        );
        $this->repository->save($booking1);

        // 2. Search for conflict 10:30-11:30 (Overlaps)
        $conflicts = $this->repository->findConflicting(
            $resourceId->toString(),
            new DateTimeImmutable('2026-02-01 10:30:00'),
            new DateTimeImmutable('2026-02-01 11:30:00')
        );

        $this->assertCount(1, $conflicts);
        $this->assertEquals('b1', $conflicts[0]->id()->toString());
    }
}
