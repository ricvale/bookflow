<?php

declare(strict_types=1);

namespace BookFlow\Application\Shared\Interfaces;

use BookFlow\Domain\Shared\TenantId;

interface TenantContextInterface
{
    /**
     * Resolve the current tenant ID from the environment.
     * Throws an exception if no tenant can be resolved (context is required).
     */
    public function getTenantId(): TenantId;

    public function setTenantId(TenantId $tenantId): void;
}
