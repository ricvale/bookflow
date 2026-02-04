<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Auth;

use BookFlow\Application\Shared\Interfaces\UserContextInterface;
use BookFlow\Domain\User\User;
use BookFlow\Domain\User\UserId;
use RuntimeException;

/**
 * In-memory implementation of UserContext.
 *
 * Stores the current user for the duration of the request.
 * Set by AuthMiddleware after successful JWT validation.
 */
final class InMemoryUserContext implements UserContextInterface
{
    private ?User $user = null;

    public function getUser(): User
    {
        if ($this->user === null) {
            throw new RuntimeException('No authenticated user in context');
        }
        return $this->user;
    }

    public function getUserId(): UserId
    {
        return $this->getUser()->id();
    }

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function clear(): void
    {
        $this->user = null;
    }
}
