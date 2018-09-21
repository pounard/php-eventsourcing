<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore;

use Ramsey\Uuid\UuidInterface;

interface EventStore
{
    /**
     * Store event
     */
    public function store(Event $event): Event;

    /**
     * Create event query
     */
    public function createQuery(): EventQuery;

    /**
     * @return EventStream|Event[]
     */
    public function getAllEvents(array $names = []): EventStream;

    /**
     * @return EventStream|Event[]
     */
    public function getEventsFor(UuidInterface $aggregateId, array $names = []): EventStream;

    /**
     * @return EventStream|Event[]
     */
    public function getEventsWith(ConcreteEventQuery $query): EventStream;
}
