<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Bridge\Goat\Tests;

use Goat\Runner\RunnerInterface;
use MakinaCorpus\EventSourcing\Bridge\Goat\GoatSnapshotStoreFactory;
use MakinaCorpus\EventSourcing\SnapshotStore\SnapshotStore;
use MakinaCorpus\EventSourcing\SnapshotStore\Tests\SnapshotStoreTest;

abstract class AbstractGoatSnapshotStoreTest extends SnapshotStoreTest
{
    abstract protected function createRunner(): RunnerInterface;

    private function createSnapshotStoreFrom(RunnerInterface $runner): SnapshotStore
    {
        $namespace = \uniqid('test_');
        $factory = new GoatSnapshotStoreFactory($runner);
        $store = $factory->getSnapshotStore($namespace);

        return $store;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSnapshotStore(): SnapshotStore
    {
        return $this->createSnapshotStoreFrom($this->createRunner());
    }
}
