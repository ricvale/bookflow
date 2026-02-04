<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Auth;

use BookFlow\Application\Auth\JwtGeneratorInterface;
use BookFlow\Application\Auth\JwtValidatorInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtTokenGenerator implements JwtGeneratorInterface, JwtValidatorInterface
{
    private string $secret;
    private string $algo;

    public function __construct(string $secret = 'default_secret_change_me', string $algo = 'HS256')
    {
        $this->secret = $secret; // Should come from ENV in production
        $this->algo = $algo;
    }

    public function generate(array $payload): string
    {
        // Add standard claims if not present
        if (!isset($payload['iat'])) {
            $payload['iat'] = time();
        }
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + (60 * 60); // 1 hour expiration
        }

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function validate(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid token: ' . $e->getMessage());
        }
    }
}
