<?php

declare(strict_types=1);

namespace BookFlow\Domain\User;

/**
 * Repository interface for User aggregate.
 */
interface UserRepository
{
    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their ID.
     */
    public function findById(UserId $id): ?User;

    /**
     * Save a user (insert or update).
     */
    public function save(User $user): void;
}
