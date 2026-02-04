<?php

declare(strict_types=1);

namespace BookFlow\Http\Exception;

/**
 * Thrown when authentication fails or is missing.
 */
final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }

    public static function noToken(): self
    {
        return new self('Authorization token is required');
    }

    public static function invalidToken(string $reason = ''): self
    {
        $message = 'Invalid authorization token';
        if ($reason) {
            $message .= ': ' . $reason;
        }
        return new self($message);
    }

    public static function expiredToken(): self
    {
        return new self('Authorization token has expired');
    }
}
