<?php

declare(strict_types=1);

namespace BookFlow\Tests\Unit\Application\Booking;

use BookFlow\Application\Booking\CancelBooking;
use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\BookingId;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\BookingStatus;
use BookFlow\Domain\Booking\CancellationPolicy;
use BookFlow\Domain\Booking\Exception\BookingNotFoundException;
use BookFlow\Domain\Booking\Exception\CancellationNotAllowedException;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Shared\DateRange;
use BookFlow\Domain\Shared\TenantId;
use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CancelBookingTest extends TestCase
{
    private BookingRepositoryInterface&MockObject $bookings;
    private TenantContextInterface&MockObject $tenantContext;

    protected function setUp(): void
    {
        $this->bookings = $this->createMock(BookingRepositoryInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
    }

    public function testThrowsWhenBookingNotFound(): void
    {
        $id = BookingId::fromString('booking-123');
        $this->bookings->method('findByBookingId')->with($id)->willReturn(null);

        $useCase = new CancelBooking(
            $this->bookings,
            $this->tenantContext,
            new CancellationPolicy(24)
        );

        $this->expectException(BookingNotFoundException::class);
        $useCase->execute('booking-123');
    }

    public function testThrowsWhenTenantMismatch(): void
    {
        $tenantId = TenantId::fromString('tenant-123');
        $otherTenantId = TenantId::fromString('tenant-456');
        $booking = $this->createBooking('booking-123', $tenantId, '2026-03-01 10:00');

        $this->bookings->method('findByBookingId')->willReturn($booking);
        $this->tenantContext->method('getTenantId')->willReturn($otherTenantId);

        $useCase = new CancelBooking(
            $this->bookings,
            $this->tenantContext,
            new CancellationPolicy(24)
        );

        $this->expectException(BookingNotFoundException::class);
        $useCase->execute('booking-123');
    }

    public function testThrowsWhenCancellationNotAllowedByPolicy(): void
    {
        $tenantId = TenantId::fromString('tenant-123');
        $booking = $this->createBooking('booking-123', $tenantId, '2026-02-19 10:00');
        $now = new DateTimeImmutable('2026-02-19 09:00'); // 1 hour before start; policy requires 24h

        $this->bookings->method('findByBookingId')->willReturn($booking);
        $this->tenantContext->method('getTenantId')->willReturn($tenantId);

        $useCase = new CancelBooking(
            $this->bookings,
            $this->tenantContext,
            new CancellationPolicy(24),
            null,
            fn () => $now
        );

        $this->expectException(CancellationNotAllowedException::class);
        $this->expectExceptionMessage('at least 24 hour(s) before');
        $useCase->execute('booking-123');
    }

    public function testCancelsWhenPolicyAllows(): void
    {
        $tenantId = TenantId::fromString('tenant-123');
        $booking = $this->createBooking('booking-123', $tenantId, '2026-03-01 10:00'); // far in future

        $this->bookings->method('findByBookingId')->willReturn($booking);
        $this->tenantContext->method('getTenantId')->willReturn($tenantId);
        $this->bookings->expects($this->once())->method('save')->with($booking);

        $useCase = new CancelBooking(
            $this->bookings,
            $this->tenantContext,
            new CancellationPolicy(24)
        );

        $useCase->execute('booking-123');

        $this->assertSame(BookingStatus::CANCELLED, $booking->status());
    }

    private function createBooking(string $id, TenantId $tenantId, string $startDate): Booking
    {
        $timeSlot = new DateRange(
            new DateTimeImmutable($startDate),
            (new DateTimeImmutable($startDate))->modify('+1 hour')
        );
        return Booking::reconstitute(
            BookingId::fromString($id),
            $tenantId,
            ResourceId::fromString('resource-1'),
            $timeSlot,
            BookingStatus::CONFIRMED,
            new DateTimeImmutable()
        );
    }
}
