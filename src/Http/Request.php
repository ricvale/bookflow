<?php

declare(strict_types=1);

namespace BookFlow\Http;

/**
 * Simple request wrapper for HTTP requests.
 */
final class Request
{
    private array $parsedBody;

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        private readonly string $rawBody
    ) {
        $this->parsedBody = [];
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach (getallheaders() as $name => $value) {
            $headers[strtolower($name)] = $value;
        }

        return new self(
            method: $_SERVER['REQUEST_METHOD'],
            path: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/',
            query: $_GET,
            headers: $headers,
            rawBody: file_get_contents('php://input') ?: ''
        );
    }

    public function getJson(): array
    {
        if (empty($this->parsedBody)) {
            $this->parsedBody = json_decode($this->rawBody, true) ?? [];
        }
        return $this->parsedBody;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('authorization');
        if ($auth && preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }
}
