<?php

declare(strict_types=1);

namespace BookFlow\Application\Shared\Interfaces;

use BookFlow\Domain\User\User;
use BookFlow\Domain\User\UserId;

/**
 * Interface for resolving the current authenticated user.
 *
 * Similar to TenantContextInterface, this provides the current user
 * which is set by the authentication middleware.
 */
interface UserContextInterface
{
    /**
     * Get the currently authenticated user.
     *
     * @throws \RuntimeException If no user is authenticated
     */
    public function getUser(): User;

    /**
     * Get the current user's ID.
     *
     * @throws \RuntimeException If no user is authenticated
     */
    public function getUserId(): UserId;

    /**
     * Check if a user is currently authenticated.
     */
    public function isAuthenticated(): bool;

    /**
     * Set the current user (called by authentication middleware).
     */
    public function setUser(User $user): void;

    /**
     * Clear the current user context.
     */
    public function clear(): void;
}
