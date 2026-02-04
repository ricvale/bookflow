<?php

declare(strict_types=1);

namespace BookFlow\Tests\Unit\Infrastructure\Auth;

use BookFlow\Domain\Shared\TenantId;
use BookFlow\Infrastructure\Auth\InMemoryTenantContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InMemoryTenantContextTest extends TestCase
{
    public function testCanSetAndGetTenantId(): void
    {
        $context = new InMemoryTenantContext();
        $tenantId = TenantId::fromString('tenant-123');

        $context->setTenantId($tenantId);

        $this->assertTrue($tenantId->equals($context->getTenantId()));
    }

    public function testThrowsWhenNoTenantSet(): void
    {
        $context = new InMemoryTenantContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No tenant context set');

        $context->getTenantId();
    }

    public function testCanOverwriteTenantId(): void
    {
        $context = new InMemoryTenantContext();

        $tenant1 = TenantId::fromString('tenant-123');
        $tenant2 = TenantId::fromString('tenant-456');

        $context->setTenantId($tenant1);
        $context->setTenantId($tenant2);

        $this->assertTrue($tenant2->equals($context->getTenantId()));
        $this->assertFalse($tenant1->equals($context->getTenantId()));
    }
}
