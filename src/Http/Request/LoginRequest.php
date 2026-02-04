<?php

declare(strict_types=1);

namespace BookFlow\Http\Request;

use BookFlow\Http\Exception\ValidationException;

/**
 * Request DTO for user login.
 */
final readonly class LoginRequest
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }

    /**
     * Create from raw request data with validation.
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        if (!isset($data['email']) || empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (!isset($data['password']) || empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors);
        }

        return new self(
            email: $data['email'],
            password: $data['password']
        );
    }
}
