<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;

/**
 * Simple dependency injection container.
 *
 * Features:
 * - Singleton and factory bindings
 * - Auto-wiring via reflection
 * - Interface to implementation binding
 */
final class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    /**
     * Bind a factory closure to an abstract type.
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Bind a singleton - will only be instantiated once.
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
        $this->singletons[$abstract] = true;
    }

    /**
     * Register an existing instance as a singleton.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve and return an instance of the given type.
     *
     * @template T of object
     * @param class-string<T> $abstract
     * @return T
     */
    public function get(string $abstract): object
    {
        // Check if we have a cached singleton instance
        if (isset($this->instances[$abstract])) {
            $instance = $this->instances[$abstract];
            /** @var T */
            return $this->instances[$abstract];
        }

        // Resolve using binding or auto-wiring
        $instance = $this->resolve($abstract);

        // Cache if it's a singleton
        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = $instance;
        }

        /** @var T */
        return $instance;
    }

    /**
     * Check if a binding exists.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve a type, using binding or auto-wiring.
     */
    private function resolve(string $abstract): object
    {
        // If we have a binding, use it
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // Try auto-wiring
        return $this->autowire($abstract);
    }

    /**
     * Auto-wire a class by analyzing its constructor.
     */
    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new Exception("Cannot autowire: class {$class} does not exist");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Cannot autowire: {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = array_map(
            fn (ReflectionParameter $param) => $this->resolveDependency($param),
            $constructor->getParameters()
        );

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve a single constructor dependency.
     */
    private function resolveDependency(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        // If no type hint, check for default value
        if ($type === null) {
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }
            throw new Exception(
                "Cannot autowire parameter \${$param->getName()}: no type hint and no default value"
            );
        }

        // Handle union types by taking the first type
        if ($type instanceof \ReflectionUnionType) {
            $types = $type->getTypes();
            $type = $types[0] ?? null;
        }

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            // Built-in types need default values
            if ($type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                if ($type->allowsNull()) {
                    return null;
                }
                throw new Exception(
                    "Cannot autowire built-in type for \${$param->getName()}"
                );
            }

            // Recursively resolve class dependencies
            /** @var class-string<object> $typeName */
            return $this->get($typeName);
        }

        throw new Exception("Cannot resolve parameter \${$param->getName()}");
    }

    /**
     * Call a method on a resolved class with auto-wired dependencies.
     */
    public function call(callable $callable, array $parameters = []): mixed
    {
        if (is_array($callable)) {
            [$class, $method] = $callable;
            $reflection = new \ReflectionMethod($class, $method);
        } else {
            $reflection = new \ReflectionFunction($callable instanceof Closure ? $callable : Closure::fromCallable($callable));
        }

        $dependencies = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();

            // Use provided parameters first
            if (array_key_exists($name, $parameters)) {
                $dependencies[] = $parameters[$name];
                continue;
            }

            // Otherwise try to resolve
            $dependencies[] = $this->resolveDependency($param);
        }

        return $callable(...$dependencies);
    }
}
