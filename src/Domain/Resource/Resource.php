<?php

declare(strict_types=1);

namespace BookFlow\Domain\Resource;

use BookFlow\Domain\Shared\TenantId;

/**
 * Resource entity.
 *
 * Represents something that can be booked (e.g., a room, a desk, a piece of equipment).
 */
final class Resource
{
    public function __construct(
        private ResourceId $id,
        private TenantId $tenantId,
        private string $name,
        private string $description
    ) {
    }

    public static function create(
        ResourceId $id,
        TenantId $tenantId,
        string $name,
        string $description
    ): self {
        return new self($id, $tenantId, $name, $description);
    }

    public function id(): ResourceId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }
}
