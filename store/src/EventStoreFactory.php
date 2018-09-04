<?php

namespace MakinaCorpus\EventSourcing\EventStore;

/**
 * Creates event stores from the given namespace.
 *
 * Beware that depending upon the implementation, implicit non-initialized
 * namespace creation can be denied, and raise exceptions.
 */
interface EventStoreFactory
{
    /**
     * Get event store for given namespace
     */
    public function getEventStore(string $namespace = Event::NAMESPACE_DEFAULT): EventStore;
}
