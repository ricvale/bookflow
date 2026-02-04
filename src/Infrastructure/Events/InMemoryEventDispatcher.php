<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Events;

use BookFlow\Application\Shared\Interfaces\EventDispatcherInterface;

/**
 * Simple in-memory event dispatcher.
 *
 * Dispatches events synchronously to registered handlers.
 * In production, you might want to add async/queue support.
 */
final class InMemoryEventDispatcher implements EventDispatcherInterface
{
    /** @var array<string, callable[]> */
    private array $handlers = [];

    public function dispatch(object $event): void
    {
        $eventClass = get_class($event);

        // Dispatch to exact class handlers
        if (isset($this->handlers[$eventClass])) {
            foreach ($this->handlers[$eventClass] as $handler) {
                $handler($event);
            }
        }

        // Also dispatch to parent class/interface handlers
        foreach (class_parents($event) as $parentClass) {
            if (isset($this->handlers[$parentClass])) {
                foreach ($this->handlers[$parentClass] as $handler) {
                    $handler($event);
                }
            }
        }

        foreach (class_implements($event) as $interface) {
            if (isset($this->handlers[$interface])) {
                foreach ($this->handlers[$interface] as $handler) {
                    $handler($event);
                }
            }
        }
    }

    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }

    public function subscribe(string $eventClass, callable $handler): void
    {
        if (!isset($this->handlers[$eventClass])) {
            $this->handlers[$eventClass] = [];
        }
        $this->handlers[$eventClass][] = $handler;
    }

    /**
     * Clear all handlers (useful for testing).
     */
    public function clear(): void
    {
        $this->handlers = [];
    }

    /**
     * Get count of handlers for a specific event type.
     */
    public function getHandlerCount(string $eventClass): int
    {
        return count($this->handlers[$eventClass] ?? []);
    }
}
