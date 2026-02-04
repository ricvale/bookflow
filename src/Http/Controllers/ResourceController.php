<?php

declare(strict_types=1);

namespace BookFlow\Http\Controllers;

use BookFlow\Application\Resource\CreateResource;
use BookFlow\Domain\Resource\ResourceRepositoryInterface;
use BookFlow\Http\Request\CreateResourceRequest;

/**
 * HTTP Controller for resource operations.
 */
final class ResourceController
{
    public function __construct(
        private ResourceRepositoryInterface $resources,
        private CreateResource $createResource
    ) {
    }

    /**
     * List all resources for the current tenant.
     */
    public function index(): array
    {
        $resources = $this->resources->findAll();

        return [
            'resources' => array_map(fn ($r) => [
                'id' => $r->id()->toString(),
                'name' => $r->name(),
                'description' => $r->description(),
            ], $resources),
        ];
    }

    /**
     * Create a new resource.
     */
    public function store(array $input): array
    {
        // Validate request using DTO
        $request = CreateResourceRequest::fromArray($input);

        $resource = $this->createResource->execute(
            name: $request->name,
            description: $request->description
        );

        return [
            'message' => 'Resource created',
            'resource' => [
                'id' => $resource->id()->toString(),
                'name' => $resource->name(),
                'description' => $resource->description(),
            ],
        ];
    }
}
