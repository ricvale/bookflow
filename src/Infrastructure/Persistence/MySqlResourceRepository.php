<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Persistence;

use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Domain\Resource\Resource;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Resource\ResourceRepositoryInterface;
use BookFlow\Domain\Shared\TenantId;
use PDO;

/**
 * MySQL implementation of ResourceRepository.
 */
final class MySqlResourceRepository implements ResourceRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private TenantContextInterface $tenantContext
    ) {
    }

    public function findById(ResourceId $id): ?Resource
    {
        $tenantId = $this->tenantContext->getTenantId();

        $stmt = $this->pdo->prepare('
            SELECT * FROM resources 
            WHERE id = :id AND tenant_id = :tenant_id
        ');

        $stmt->execute([
            'id' => $id->toString(),
            'tenant_id' => $tenantId->toString(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $tenantId = $this->tenantContext->getTenantId();

        $stmt = $this->pdo->prepare('
            SELECT * FROM resources 
            WHERE tenant_id = :tenant_id
            ORDER BY name ASC
        ');

        $stmt->execute(['tenant_id' => $tenantId->toString()]);

        return array_map(
            fn ($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function save(Resource $resource): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO resources (id, tenant_id, name, description, created_at)
            VALUES (:id, :tenant_id, :name, :description, NOW())
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                description = VALUES(description)
        ');

        $stmt->execute([
            'id' => $resource->id()->toString(),
            'tenant_id' => $resource->tenantId()->toString(),
            'name' => $resource->name(),
            'description' => $resource->description(),
        ]);
    }

    private function hydrate(array $row): Resource
    {
        return new Resource(
            id: ResourceId::fromString($row['id']),
            tenantId: TenantId::fromString($row['tenant_id']),
            name: $row['name'],
            description: $row['description']
        );
    }
}
