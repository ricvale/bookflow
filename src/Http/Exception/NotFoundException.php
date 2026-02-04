<?php

declare(strict_types=1);

namespace BookFlow\Http\Exception;

/**
 * Thrown when a requested resource is not found.
 */
final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404);
    }

    public static function forResource(string $resourceType, string $id): self
    {
        return new self(sprintf('%s with ID %s not found', $resourceType, $id));
    }

    public static function route(string $path): self
    {
        return new self(sprintf('Route not found: %s', $path));
    }
}
