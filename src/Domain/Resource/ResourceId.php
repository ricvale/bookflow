<?php

declare(strict_types=1);

namespace BookFlow\Domain\Resource;

use InvalidArgumentException;

/**
 * Value Object representing a unique Resource identifier.
 */
final class ResourceId
{
    private string $id;

    private function __construct(string $id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Resource ID cannot be empty.');
        }
        $this->id = $id;
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public static function generate(): self
    {
        return new self(uniqid('resource_', true));
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function equals(ResourceId $other): bool
    {
        return $this->id === $other->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
