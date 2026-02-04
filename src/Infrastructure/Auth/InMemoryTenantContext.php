<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Auth;

use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Domain\Shared\TenantId;
use RuntimeException;

final class InMemoryTenantContext implements TenantContextInterface
{
    private ?TenantId $currentTenantId = null;

    public function setTenantId(TenantId $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    public function getTenantId(): TenantId
    {
        if ($this->currentTenantId === null) {
            throw new RuntimeException('No tenant context set in InMemory implementation.');
        }

        return $this->currentTenantId;
    }
}
