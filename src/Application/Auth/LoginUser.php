<?php

declare(strict_types=1);

namespace BookFlow\Application\Auth;

use BookFlow\Domain\User\UserRepository;
use InvalidArgumentException;

final class LoginUser
{
    public function __construct(
        private UserRepository $userRepository,
        private JwtGeneratorInterface $jwtGenerator
    ) {
    }

    public function execute(LoginCommand $command): string
    {
        $user = $this->userRepository->findByEmail($command->email);

        if (!$user) {
            throw new InvalidArgumentException('Invalid credentials.');
        }

        if (!password_verify($command->password, $user->passwordHash())) {
            throw new InvalidArgumentException('Invalid credentials.');
        }

        // Generate Token
        return $this->jwtGenerator->generate([
            'user_id' => $user->id()->toString(),
            'email' => $user->email(),
            'tenant_id' => $user->tenantId()->toString(),
            'roles' => $user->roles(),
        ]);
    }
}
