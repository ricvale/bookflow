<?php

declare(strict_types=1);

namespace BookFlow\Application\Resource;

use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Domain\Resource\Resource;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Resource\ResourceRepositoryInterface;

/**
 * Use case for creating a new resource.
 */
final class CreateResource
{
    public function __construct(
        private ResourceRepositoryInterface $resourceRepository,
        private TenantContextInterface $tenantContext
    ) {
    }

    /**
     * Execute the resource creation.
     */
    public function execute(string $name, string $description): Resource
    {
        $tenantId = $this->tenantContext->getTenantId();

        $resource = new Resource(
            id: ResourceId::generate(),
            tenantId: $tenantId,
            name: $name,
            description: $description
        );

        $this->resourceRepository->save($resource);

        return $resource;
    }
}
