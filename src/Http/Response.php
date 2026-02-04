<?php

declare(strict_types=1);

namespace BookFlow\Http;

/**
 * Simple response wrapper for HTTP responses.
 */
final class Response
{
    private function __construct(
        public readonly mixed $body,
        public readonly int $status,
        public readonly array $headers
    ) {
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self($data, $status, array_merge(
            ['Content-Type' => 'application/json'],
            $headers
        ));
    }

    public static function error(string $message, int $status = 500, array $extra = []): self
    {
        return self::json(array_merge(['error' => $message], $extra), $status);
    }

    public static function notFound(string $message = 'Not found'): self
    {
        return self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::error($message, 401);
    }

    public static function validationError(string $message, array $errors = []): self
    {
        return self::json([
            'error' => $message,
            'validation_errors' => $errors,
        ], 422);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if ($this->body !== null) {
            echo json_encode($this->body, JSON_PRETTY_PRINT);
        }
    }
}
