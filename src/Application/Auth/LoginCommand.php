<?php

declare(strict_types=1);

namespace BookFlow\Application\Auth;

final class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}
