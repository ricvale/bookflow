<?php

declare(strict_types=1);

namespace BookFlow\Domain\Resource;

interface ResourceRepositoryInterface
{
    public function findById(ResourceId $id): ?Resource;

    /**
     * Find all resources for current tenant
     * @return Resource[]
     */
    public function findAll(): array;

    public function save(Resource $resource): void;
}
