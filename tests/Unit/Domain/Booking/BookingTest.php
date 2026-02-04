<?php

declare(strict_types=1);

namespace BookFlow\Tests\Unit\Domain\Booking;

use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\BookingStatus;
use BookFlow\Domain\Booking\Events\BookingCancelled;
use BookFlow\Domain\Booking\Events\BookingCreated;
use BookFlow\Domain\Shared\DateRange;
use BookFlow\Domain\Shared\TenantId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    public function testCreatesBooking(): void
    {
        $tenantId = TenantId::fromString('tenant-123');
        $timeSlot = new DateRange(
            new DateTimeImmutable('2026-01-16 09:00'),
            new DateTimeImmutable('2026-01-16 10:00')
        );

        $booking = Booking::create(
            id: \BookFlow\Domain\Booking\BookingId::fromString('booking-123'),
            tenantId: $tenantId,
            resourceId: \BookFlow\Domain\Resource\ResourceId::fromString('resource-456'),
            timeSlot: $timeSlot
        );

        $this->assertEquals('booking-123', $booking->id()->toString());
        $this->assertEquals($tenantId, $booking->tenantId());
        $this->assertEquals('resource-456', $booking->resourceId()->toString());
        $this->assertEquals(BookingStatus::CONFIRMED, $booking->status());
    }

    public function testEmitsBookingCreatedEvent(): void
    {
        $booking = Booking::create(
            id: \BookFlow\Domain\Booking\BookingId::fromString('booking-123'),
            tenantId: TenantId::fromString('tenant-123'),
            resourceId: \BookFlow\Domain\Resource\ResourceId::fromString('resource-456'),
            timeSlot: new DateRange(
                new DateTimeImmutable('2026-01-16 09:00'),
                new DateTimeImmutable('2026-01-16 10:00')
            )
        );

        $events = $booking->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(BookingCreated::class, $events[0]);
    }

    public function testCancelsBooking(): void
    {
        $booking = Booking::create(
            id: \BookFlow\Domain\Booking\BookingId::fromString('booking-123'),
            tenantId: TenantId::fromString('tenant-123'),
            resourceId: \BookFlow\Domain\Resource\ResourceId::fromString('resource-456'),
            timeSlot: new DateRange(
                new DateTimeImmutable('2026-01-16 09:00'),
                new DateTimeImmutable('2026-01-16 10:00')
            )
        );

        $booking->releaseEvents(); // Clear creation event

        $booking->cancel();

        $this->assertEquals(BookingStatus::CANCELLED, $booking->status());

        $events = $booking->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(BookingCancelled::class, $events[0]);
    }

    public function testDetectsOverlappingBookings(): void
    {
        $booking1 = Booking::create(
            id: \BookFlow\Domain\Booking\BookingId::fromString('booking-1'),
            tenantId: TenantId::fromString('tenant-123'),
            resourceId: \BookFlow\Domain\Resource\ResourceId::fromString('resource-456'),
            timeSlot: new DateRange(
                new DateTimeImmutable('2026-01-16 09:00'),
                new DateTimeImmutable('2026-01-16 10:00')
            )
        );

        $booking2 = Booking::create(
            id: \BookFlow\Domain\Booking\BookingId::fromString('booking-2'),
            tenantId: TenantId::fromString('tenant-123'),
            resourceId: \BookFlow\Domain\Resource\ResourceId::fromString('resource-456'),
            timeSlot: new DateRange(
                new DateTimeImmutable('2026-01-16 09:30'),
                new DateTimeImmutable('2026-01-16 10:30')
            )
        );

        $this->assertTrue($booking1->overlaps($booking2));
    }

    public function testCancelledBookingsDoNotOverlap(): void
    {
        $booking1 = Booking::create(
            id: \BookFlow\Domain\Booking\BookingId::fromString('booking-1'),
            tenantId: TenantId::fromString('tenant-123'),
            resourceId: \BookFlow\Domain\Resource\ResourceId::fromString('resource-456'),
            timeSlot: new DateRange(
                new DateTimeImmutable('2026-01-16 09:00'),
                new DateTimeImmutable('2026-01-16 10:00')
            )
        );

        $booking2 = Booking::create(
            id: \BookFlow\Domain\Booking\BookingId::fromString('booking-2'),
            tenantId: TenantId::fromString('tenant-123'),
            resourceId: \BookFlow\Domain\Resource\ResourceId::fromString('resource-456'),
            timeSlot: new DateRange(
                new DateTimeImmutable('2026-01-16 09:30'),
                new DateTimeImmutable('2026-01-16 10:30')
            )
        );

        $booking1->cancel();

        $this->assertFalse($booking1->overlaps($booking2));
    }
}
