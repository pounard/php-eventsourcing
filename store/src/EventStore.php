<?php

namespace MakinaCorpus\EventSourcing\EventStore;

use Ramsey\Uuid\UuidInterface;

interface EventStore
{
    /**
     * Get namespace
     */
    public function getNamespace(): string;

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
    public function getEventsWith(ConcretEventQuery $query): EventStream;
}
