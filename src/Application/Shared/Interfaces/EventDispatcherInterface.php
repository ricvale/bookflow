<?php

declare(strict_types=1);

namespace BookFlow\Application\Shared\Interfaces;

/**
 * Interface for dispatching domain events to registered handlers.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch a single domain event to all registered handlers.
     */
    public function dispatch(object $event): void;

    /**
     * Dispatch multiple domain events.
     *
     * @param object[] $events
     */
    public function dispatchAll(array $events): void;

    /**
     * Register a handler for a specific event type.
     *
     * @param string $eventClass The fully qualified class name of the event
     * @param callable $handler The handler function/callable
     */
    public function subscribe(string $eventClass, callable $handler): void;
}
