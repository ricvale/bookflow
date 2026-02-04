<?php

declare(strict_types=1);

namespace BookFlow\Domain\User;

use InvalidArgumentException;

final class UserId
{
    private string $id;

    private function __construct(string $id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('User ID cannot be empty.');
        }
        $this->id = $id;
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function equals(UserId $other): bool
    {
        return $this->id === $other->id;
    }
}
