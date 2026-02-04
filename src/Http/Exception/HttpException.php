<?php

declare(strict_types=1);

namespace BookFlow\Http\Exception;

use Exception;

/**
 * Base exception for all HTTP-related errors.
 */
abstract class HttpException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly array $context = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function toArray(): array
    {
        return [
            'error' => $this->getMessage(),
            'status' => $this->statusCode,
            ...$this->context,
        ];
    }
}
