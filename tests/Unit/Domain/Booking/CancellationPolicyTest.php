<?php

declare(strict_types=1);

namespace BookFlow\Tests\Unit\Domain\Booking;

use BookFlow\Domain\Booking\CancellationPolicy;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CancellationPolicyTest extends TestCase
{
    public function testAllowsCancellationWhenMoreThanMinimumHoursBeforeStart(): void
    {
        $policy = new CancellationPolicy(24);
        $bookingStart = new DateTimeImmutable('2026-02-20 10:00:00');
        $now = new DateTimeImmutable('2026-02-18 09:00:00'); // 49 hours before

        $this->assertTrue($policy->allowsCancellation($bookingStart, $now));
    }

    public function testAllowsCancellationWhenExactlyMinimumHoursBeforeStart(): void
    {
        $policy = new CancellationPolicy(24);
        $bookingStart = new DateTimeImmutable('2026-02-20 10:00:00');
        $now = new DateTimeImmutable('2026-02-19 10:00:00'); // exactly 24 hours before

        $this->assertTrue($policy->allowsCancellation($bookingStart, $now));
    }

    public function testDeniesCancellationWhenLessThanMinimumHoursBeforeStart(): void
    {
        $policy = new CancellationPolicy(24);
        $bookingStart = new DateTimeImmutable('2026-02-20 10:00:00');
        $now = new DateTimeImmutable('2026-02-19 11:00:00'); // 23 hours before

        $this->assertFalse($policy->allowsCancellation($bookingStart, $now));
    }

    public function testDeniesCancellationWhenBookingStartInPast(): void
    {
        $policy = new CancellationPolicy(24);
        $bookingStart = new DateTimeImmutable('2026-02-18 08:00:00');
        $now = new DateTimeImmutable('2026-02-18 10:00:00');

        $this->assertFalse($policy->allowsCancellation($bookingStart, $now));
    }

    public function testDeniesCancellationWhenBookingStartIsNow(): void
    {
        $policy = new CancellationPolicy(24);
        $now = new DateTimeImmutable('2026-02-18 10:00:00');
        $bookingStart = $now;

        $this->assertFalse($policy->allowsCancellation($bookingStart, $now));
    }

    public function testZeroHourPolicyAllowsCancellationUntilStart(): void
    {
        $policy = new CancellationPolicy(0);
        $bookingStart = new DateTimeImmutable('2026-02-20 10:00:00');
        $now = new DateTimeImmutable('2026-02-20 09:59:00');

        $this->assertTrue($policy->allowsCancellation($bookingStart, $now));
    }

    public function testZeroHourPolicyDeniesCancellationAfterStart(): void
    {
        $policy = new CancellationPolicy(0);
        $bookingStart = new DateTimeImmutable('2026-02-20 10:00:00');
        $now = new DateTimeImmutable('2026-02-20 10:01:00');

        $this->assertFalse($policy->allowsCancellation($bookingStart, $now));
    }
}
