<?php

declare(strict_types=1);

namespace BookFlow\Http;

/**
 * Represents a matched route with parameters.
 */
final class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly mixed $handler,
        public readonly bool $protected = true,
        public readonly array $middleware = []
    ) {
    }
}

/**
 * Simple router for matching HTTP requests to handlers.
 */
final class Router
{
    /** @var Route[] */
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $pattern, mixed $handler, bool $protected = true): self
    {
        return $this->addRoute('GET', $pattern, $handler, $protected);
    }

    /**
     * Register a POST route.
     */
    public function post(string $pattern, mixed $handler, bool $protected = true): self
    {
        return $this->addRoute('POST', $pattern, $handler, $protected);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $pattern, mixed $handler, bool $protected = true): self
    {
        return $this->addRoute('PUT', $pattern, $handler, $protected);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $pattern, mixed $handler, bool $protected = true): self
    {
        return $this->addRoute('DELETE', $pattern, $handler, $protected);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $pattern, mixed $handler, bool $protected = true): self
    {
        return $this->addRoute('PATCH', $pattern, $handler, $protected);
    }

    private function addRoute(string $method, string $pattern, mixed $handler, bool $protected): self
    {
        $this->routes[] = new Route($method, $pattern, $handler, $protected);
        return $this;
    }

    /**
     * Match a request to a route.
     *
     * @return array{route: Route, params: array<string, string>}|null
     */
    public function match(Request $request): ?array
    {
        foreach ($this->routes as $route) {
            if ($route->method !== $request->method) {
                continue;
            }

            $params = $this->matchPattern($route->pattern, $request->path);
            if ($params !== null) {
                return ['route' => $route, 'params' => $params];
            }
        }

        return null;
    }

    /**
     * Match a pattern against a path, extracting parameters.
     *
     * Supports patterns like:
     * - /api/bookings
     * - /api/bookings/{id}
     * - /api/resources/{resourceId}/bookings
     *
     * @return array<string, string>|null Parameters if matched, null otherwise
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        // Convert pattern to regex
        $regex = preg_replace_callback(
            '/\{([^}]+)\}/',
            fn ($matches) => '(?P<' . $matches[1] . '>[^/]+)',
            $pattern
        );
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            // Filter to only named captures
            return array_filter($matches, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    /**
     * Get all registered routes (for debugging).
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
