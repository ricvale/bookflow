<?php

declare(strict_types=1);

namespace BookFlow\Tests\Unit\Domain\Shared;

use BookFlow\Domain\Shared\DateRange;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function testCreatesValidDateRange(): void
    {
        $starts = new DateTimeImmutable('2026-01-16 09:00');
        $ends = new DateTimeImmutable('2026-01-16 10:00');

        $range = new DateRange($starts, $ends);

        $this->assertEquals($starts, $range->startsAt());
        $this->assertEquals($ends, $range->endsAt());
    }

    public function testRejectsEndBeforeStart(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DateRange(
            new DateTimeImmutable('2026-01-16 10:00'),
            new DateTimeImmutable('2026-01-16 09:00')
        );
    }

    public function testDetectsOverlap(): void
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2026-01-16 09:00'),
            new DateTimeImmutable('2026-01-16 10:00')
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2026-01-16 09:30'),
            new DateTimeImmutable('2026-01-16 10:30')
        );

        $this->assertTrue($range1->overlaps($range2));
        $this->assertTrue($range2->overlaps($range1));
    }

    public function testDetectsNoOverlap(): void
    {
        $range1 = new DateRange(
            new DateTimeImmutable('2026-01-16 09:00'),
            new DateTimeImmutable('2026-01-16 10:00')
        );

        $range2 = new DateRange(
            new DateTimeImmutable('2026-01-16 10:00'),
            new DateTimeImmutable('2026-01-16 11:00')
        );

        $this->assertFalse($range1->overlaps($range2));
    }

    public function testCalculatesDuration(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2026-01-16 09:00'),
            new DateTimeImmutable('2026-01-16 10:30')
        );

        $this->assertEquals(90, $range->durationInMinutes());
    }
}
