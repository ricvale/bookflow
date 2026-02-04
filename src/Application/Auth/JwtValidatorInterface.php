<?php

declare(strict_types=1);

namespace BookFlow\Application\Auth;

interface JwtValidatorInterface
{
    /**
     * @return array The decoded payload
     * @throws \InvalidArgumentException If token is invalid
     */
    public function validate(string $token): array;
}
