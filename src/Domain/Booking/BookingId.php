<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking;

use InvalidArgumentException;

/**
 * Value Object representing a unique Booking identifier.
 */
final class BookingId
{
    private string $id;

    private function __construct(string $id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Booking ID cannot be empty.');
        }
        $this->id = $id;
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public static function generate(): self
    {
        return new self(uniqid('booking_', true));
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function equals(BookingId $other): bool
    {
        return $this->id === $other->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
