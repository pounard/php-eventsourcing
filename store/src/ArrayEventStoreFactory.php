<?php

namespace MakinaCorpus\EventSourcing;

/**
 * Array-based in-memory event store factory.
 *
 * Will serve the purpose of creating unit tests, and work as a base
 * implementation for others to implement.
 *
 * @codeCoverageIgnore
 *   Code coverage does not take into account data provider run methods.
 */
final class ArrayEventStoreFactory implements EventStoreFactory
{
    private $eventStores = [];

    /**
     * Get event store for given namespace
     */
    public function getEventStore(string $namespace = Event::NAMESPACE_DEFAULT): EventStore
    {
        return $this->eventStores[$namespace] ?? (
            $this->eventStores[$namespace] = new ArrayEventStore($namespace)
        );
    }
}
