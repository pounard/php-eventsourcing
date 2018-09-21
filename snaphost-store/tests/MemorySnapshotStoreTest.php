<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore\Tests;

use MakinaCorpus\EventSourcing\SnapshotStore\MemorySnapshotStoreFactory;
use MakinaCorpus\EventSourcing\SnapshotStore\SnapshotStore;

final class MemorySnapshotStoreTest extends SnapshotStoreTest
{
    /**
     * {@inheritdoc}
     */
    protected function getSnapshotStore(): SnapshotStore
    {
        return (new MemorySnapshotStoreFactory())->getSnapshotStore(\uniqid('test_'));
    }
}
