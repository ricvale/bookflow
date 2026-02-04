<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Persistence;

use BookFlow\Domain\Shared\TenantId;
use BookFlow\Domain\User\User;
use BookFlow\Domain\User\UserId;
use BookFlow\Domain\User\UserRepository;
use PDO;

final class MySqlUserRepository implements UserRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users 
            WHERE email = :email
        ');

        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findById(UserId $id): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users 
            WHERE id = :id
        ');

        $stmt->execute(['id' => $id->toString()]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (id, tenant_id, email, password_hash, name, google_auth_data, created_at)
            VALUES (:id, :tenant_id, :email, :password_hash, :name, :google_auth_data, NOW())
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                password_hash = VALUES(password_hash),
                google_auth_data = VALUES(google_auth_data)
        ');

        $stmt->execute([
            'id' => $user->id()->toString(),
            'tenant_id' => $user->tenantId()->toString(),
            'email' => $user->email(),
            'password_hash' => $user->passwordHash(),
            'name' => $user->name(),
            'google_auth_data' => $user->googleAuthData() ? json_encode($user->googleAuthData()) : null,
        ]);
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: UserId::fromString($row['id']),
            tenantId: TenantId::fromString($row['tenant_id']),
            email: $row['email'],
            passwordHash: $row['password_hash'],
            name: $row['name'],
            googleAuthData: $row['google_auth_data'] ? json_decode($row['google_auth_data'], true) : null
        );
    }
}
