<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

use MakinaCorpus\EventSourcing\EventStore\Event;

/**
 * Array-based in-memory snapshot store reference implementation.
 *
 * It works as the reference implementation, as well as a memory cache ready
 * for production use.
 */
final class MemorySnapshotStoreFactory implements SnapshotStoreFactory
{
    private $snapshotStores = [];

    /**
     * {@inheritdoc}
     */
    public function getSnapshotStore(string $namespace = Event::NAMESPACE_DEFAULT): SnapshotStore
    {
        return $this->snapshotStores[$namespace] ?? (
            $this->snapshotStores[$namespace] = new MemorySnapshotStore()
        );
    }
}
