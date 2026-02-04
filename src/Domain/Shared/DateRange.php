<?php

declare(strict_types=1);

namespace BookFlow\Domain\Shared;

use DateTimeImmutable;
use InvalidArgumentException;

final class DateRange
{
    public function __construct(
        private DateTimeImmutable $startsAt,
        private DateTimeImmutable $endsAt
    ) {
        if ($startsAt >= $endsAt) {
            throw new InvalidArgumentException('Start time must be before end time');
        }
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function overlaps(self $other): bool
    {
        return $this->startsAt < $other->endsAt
            && $this->endsAt > $other->startsAt;
    }

    public function durationInMinutes(): int
    {
        return (int) (($this->endsAt->getTimestamp() - $this->startsAt->getTimestamp()) / 60);
    }

    public function equals(self $other): bool
    {
        return $this->startsAt == $other->startsAt
            && $this->endsAt == $other->endsAt;
    }
}
