<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Goat\Tests;

use Goat\Runner\RunnerInterface;
use MakinaCorpus\EventSourcing\EventStore\EventStore;
use MakinaCorpus\EventSourcing\EventStore\Tests\EventStoreTest;
use MakinaCorpus\EventSourcing\Goat\GoatEventStoreFactory;

abstract class AbstractGoatEventStoreTest extends EventStoreTest
{
    abstract protected function createRunner(): RunnerInterface;

    private function createEventStoreFrom(RunnerInterface $runner): EventStore
    {
        $namespace = \uniqid('test_');
        $factory = new GoatEventStoreFactory($runner);
        $store = $factory->getEventStore($namespace);

        return $store;
    }

    /**
     * {@inheritdoc}
     */
    final public function getEventStore(): EventStore
    {
        return $this->createEventStoreFrom($this->createRunner());
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCount(): int
    {
        return 200;
    }
}
