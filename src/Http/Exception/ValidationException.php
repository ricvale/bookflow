<?php

declare(strict_types=1);

namespace BookFlow\Http\Exception;

/**
 * Thrown when request validation fails.
 */
final class ValidationException extends HttpException
{
    public function __construct(
        string $message = 'Validation failed',
        public readonly array $errors = []
    ) {
        parent::__construct($message, 422, ['validation_errors' => $errors]);
    }

    public static function withErrors(array $errors): self
    {
        return new self('Validation failed', $errors);
    }

    public static function missingField(string $field): self
    {
        return new self("Missing required field: {$field}", [$field => 'This field is required']);
    }

    public static function invalidField(string $field, string $reason): self
    {
        return new self("Invalid field: {$field}", [$field => $reason]);
    }
}
