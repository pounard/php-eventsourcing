<?php

namespace MakinaCorpus\EventSourcing\Domain\Tests;

use MakinaCorpus\EventSourcing\Domain\Aggregate;
use MakinaCorpus\EventSourcing\EventStore\Event;

final class MockAggregate extends Aggregate
{
    private $handlers = [];

    public function addWhenHandler(string $eventName, callable $handler)
    {
        $this->handlers[$eventName] = $handler;
    }

    public function raiseArbitraryEvent(string $eventName, array $data)
    {
        $this->occurs($eventName, $data);
    }

    protected function when(Event $event): bool
    {
        $eventName = $event->getName();

        if (isset($this->handlers[$eventName])) {
            \call_user_func($this->handlers[$eventName], $event);

            return true;
        }

        return false;
    }
}
