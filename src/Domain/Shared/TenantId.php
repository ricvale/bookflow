<?php

declare(strict_types=1);

namespace BookFlow\Domain\Shared;

use InvalidArgumentException;

final class TenantId
{
    private string $id;

    private function __construct(string $id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Tenant ID cannot be empty.');
        }

        // In a real app, we might validate UUID format here.
        // For now, we ensure it's a non-empty string.
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

    public function equals(TenantId $other): bool
    {
        return $this->id === $other->id;
    }
}
