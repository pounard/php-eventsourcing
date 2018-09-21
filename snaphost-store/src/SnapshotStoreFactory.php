<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

use MakinaCorpus\EventSourcing\EventStore\Event;

/**
 * Creates snapshot stores from the given namespace.
 *
 * Beware that depending upon the implementation, implicit non-initialized
 * namespace creation can be denied, and raise exceptions.
 */
interface SnapshotStoreFactory
{
    /**
     * Get snapshot store for given namespace
     */
    public function getSnapshotStore(string $namespace = Event::NAMESPACE_DEFAULT): SnapshotStore;
}
