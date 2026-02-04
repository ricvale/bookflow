<?php

declare(strict_types=1);

namespace BookFlow\Http\Controllers;

use BookFlow\Application\Auth\LoginCommand;
use BookFlow\Application\Auth\LoginUser;
use BookFlow\Application\Shared\Interfaces\UserContextInterface;
use BookFlow\Http\Request\LoginRequest;

/**
 * HTTP Controller for authentication operations.
 */
final class AuthController
{
    public function __construct(
        private LoginUser $loginUser,
        private ?UserContextInterface $userContext = null
    ) {
    }

    /**
     * Get the current authenticated user's profile.
     */
    public function me(): array
    {
        if ($this->userContext === null || !$this->userContext->isAuthenticated()) {
            return [];
        }

        $user = $this->userContext->getUser();

        return [
            'id' => $user->id()->toString(),
            'name' => $user->name(),
            'email' => $user->email(),
            'is_google_connected' => $user->googleAuthData() !== null,
        ];
    }

    /**
     * Authenticate a user and return a JWT token.
     */
    public function login(array $data): array
    {
        // Validate request using DTO
        $request = LoginRequest::fromArray($data);

        $command = new LoginCommand($request->email, $request->password);
        $token = $this->loginUser->execute($command);

        return [
            'token' => $token,
        ];
    }
}
