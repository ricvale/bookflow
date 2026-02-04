<?php

declare(strict_types=1);

namespace BookFlow\Http\Middleware;

use BookFlow\Application\Auth\JwtValidatorInterface;
use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Application\Shared\Interfaces\UserContextInterface;
use BookFlow\Domain\Shared\TenantId;
use BookFlow\Domain\User\UserId;
use BookFlow\Domain\User\UserRepository;
use BookFlow\Http\Exception\UnauthorizedException;
use BookFlow\Http\Request;
use InvalidArgumentException;

/**
 * Authentication middleware.
 *
 * Validates JWT tokens and sets both Tenant and User contexts.
 */
final class AuthMiddleware
{
    public function __construct(
        private JwtValidatorInterface $jwtValidator,
        private TenantContextInterface $tenantContext,
        private ?UserContextInterface $userContext = null,
        private ?UserRepository $userRepository = null
    ) {
    }

    /**
     * Handle the request authentication.
     *
     * @throws UnauthorizedException
     */
    public function handle(?Request $request = null): void
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            throw UnauthorizedException::noToken();
        }

        try {
            $payload = $this->jwtValidator->validate($token);

            // Set Tenant Context
            if (isset($payload['tenant_id'])) {
                $this->tenantContext->setTenantId(
                    TenantId::fromString($payload['tenant_id'])
                );
            }

            // Set User Context if available
            if ($this->userContext !== null && $this->userRepository !== null && isset($payload['user_id'])) {
                $user = $this->userRepository->findById(UserId::fromString($payload['user_id']));
                if ($user !== null) {
                    $this->userContext->setUser($user);
                }
            }

        } catch (InvalidArgumentException $e) {
            throw UnauthorizedException::invalidToken($e->getMessage());
        }
    }

    /**
     * Extract the Bearer token from the request.
     */
    private function extractToken(?Request $request): ?string
    {
        // If Request object provided, use it
        if ($request !== null) {
            return $request->getBearerToken();
        }

        // Fall back to global headers
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
