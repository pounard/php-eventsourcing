<?php

namespace MakinaCorpus\EventSourcing\Tests;

use MakinaCorpus\EventSourcing\ArrayEventStoreFactory;

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
