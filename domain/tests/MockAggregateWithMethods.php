<?php

namespace MakinaCorpus\EventSourcing\Domain\Tests;

use MakinaCorpus\EventSourcing\Domain\Aggregate;
use MakinaCorpus\EventSourcing\EventStore\Event;

final class MockAggregateWithMethods extends Aggregate
{
    private $lastEvent = null;

    public function getLastEventName(): string
    {
        return $this->lastEvent;
    }

    public function somethingHappens()
    {
        $this->occurs('SomethingHappened');
    }

    protected function whenSomethingHappened(Event $event)
    {
        $this->lastEvent = $event->getName();
    }

    public function someOtherThingHappens()
    {
        $this->occurs('some_other_thing_happened');
    }

    protected function whenSomeOtherThingHappened(Event $event)
    {
        $this->lastEvent = $event->getName();
    }
}
