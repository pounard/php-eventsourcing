<?php

namespace MakinaCorpus\EventSourcing\EventStore\Tests;

use MakinaCorpus\EventSourcing\EventStore\ArrayEventStoreFactory;

/**
 * Tests the views
 */
final class ArrayEventStoreTest extends EventStoreTest
{
    /**
     * {@inheritdoc}
     */
    public function getEventStore()
    {
        yield [(new ArrayEventStoreFactory())->getEventStore(\uniqid('test-'))];
    }
}
