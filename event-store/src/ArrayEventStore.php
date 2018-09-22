<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore;

use Ramsey\Uuid\UuidInterface;

/**
 * Array-based in-memory event store reference implementation.
 *
 * Will serve the purpose of creating unit tests, and work as a base
 * implementation for others to implement.
 */
final class ArrayEventStore implements EventStore
{
    private $serial = 1;
    private $aggregateSequence = [];
    private $events = [];

    /**
     * Get next revision number for aggregate
     */
    private function getRevisionFor(UuidInterface $aggregateId): int
    {
        $key = (string)$aggregateId;

        if (!isset($this->aggregateSequence[$key])) {
            $this->aggregateSequence[$key] = 1;
        }

        return $this->aggregateSequence[$key]++;
    }

    /**
     * Get next position
     */
    private function getPositionFor(): int
    {
        return $this->serial++;
    }

    /**
     * {@inheritdoc}
     */
    public function store(Event $event): Event
    {
        $aggregateId = $event->getAggregateId();
        $aggregateType = $event->getAggregateType();
        $position = $this->getPositionFor();
        $revision = $this->getRevisionFor($aggregateId);

        // Normalize output, and check for errors at the same time
        if (false === ($data = \json_encode($event->getData()))) {
            throw new \InvalidArgumentException(\sprintf("Invalid data in event, did you set any non scalar values?"));
        }
        $data = \json_decode($data, true);

        // Create normalized, will all data event object
        $entry = Event::fromEventStore(
            $position,
            $aggregateId,
            $revision,
            $aggregateType,
            $event->createdAt(),
            $event->getName(),
            $data,
            false
        );

        $this->events[(string)$position] = $entry;

        return $entry;
    }

    /**
     * For unit testing only, used in ArrayEventStream
     */
    public function getEventArray(): array
    {
        return $this->events;
    }

    private function queryEvents(ConcreteEventQuery $query): ArrayEventStream
    {
        return new ArrayEventStream($this, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery(): EventQuery
    {
        return new ConcreteEventQuery(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllEvents(array $names = []): EventStream
    {
        return $this->queryEvents($this->createQuery()->withName($names));
    }

    /**
     * {@inheritdoc}
     */
    public function getEventsFor(UuidInterface $aggregateId, array $names = []): EventStream
    {
        return $this->queryEvents($this->createQuery()->for($aggregateId)->withName($names));
    }

    /**
     * {@inheritdoc}
     */
    public function getEventsWith(ConcreteEventQuery $query): EventStream
    {
        return $this->queryEvents($query);
    }
}
