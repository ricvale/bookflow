<?php

declare(strict_types=1);

namespace BookFlow\Tests\Unit\Domain\Shared;

use BookFlow\Domain\Shared\TenantId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TenantIdTest extends TestCase
{
    public function testCanCreateFromString(): void
    {
        $tenantId = TenantId::fromString('tenant-123');

        $this->assertEquals('tenant-123', $tenantId->toString());
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenant ID cannot be empty');

        TenantId::fromString('');
    }

    public function testEqualityWorks(): void
    {
        $tenant1 = TenantId::fromString('tenant-123');
        $tenant2 = TenantId::fromString('tenant-123');
        $tenant3 = TenantId::fromString('tenant-456');

        $this->assertTrue($tenant1->equals($tenant2));
        $this->assertFalse($tenant1->equals($tenant3));
    }

    public function testIsImmutable(): void
    {
        $tenantId = TenantId::fromString('tenant-123');
        $originalValue = $tenantId->toString();

        // Attempt to create another instance doesn't affect the original
        TenantId::fromString('tenant-456');

        $this->assertEquals($originalValue, $tenantId->toString());
    }
}
