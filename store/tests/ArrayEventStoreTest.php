<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore\Tests;

use MakinaCorpus\EventSourcing\EventStore\ArrayEventStoreFactory;
use MakinaCorpus\EventSourcing\EventStore\EventStore;

final class ArrayEventStoreTest extends EventStoreTest
{
    /**
     * {@inheritdoc}
     */
    public function getEventStore(): EventStore
    {
        return (new ArrayEventStoreFactory())->getEventStore(\uniqid('test-'));
    }
}
