<?php

declare(strict_types=1);

namespace BookFlow\Domain\User;

use BookFlow\Domain\Shared\TenantId;

final class User
{
    public function __construct(
        private UserId $id,
        private TenantId $tenantId,
        private string $email,
        private string $passwordHash,
        private string $name,
        private ?array $googleAuthData = null
    ) {
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function googleAuthData(): ?array
    {
        return $this->googleAuthData;
    }

    public function connectGoogle(array $authData): void
    {
        $this->googleAuthData = $authData;
    }

    public function roles(): array
    {
        // Simple hardcoded role for now, can be expanded later
        return ['user'];
    }
}
