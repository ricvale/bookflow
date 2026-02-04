<?php

declare(strict_types=1);

namespace BookFlow\Application\Auth;

interface JwtGeneratorInterface
{
    public function generate(array $payload): string;
}
