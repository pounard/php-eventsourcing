<?php

namespace MakinaCorpus\EventSourcing;

/**
 * Array-based in-memory event stream.
 *
 * Will serve the purpose of creating unit tests, and work as a base
 * implementation for others to implement.
 */
final class ArrayEventStream implements \IteratorAggregate, EventStream
{
    private $store;
    private $query;

    /**
     * Default constructor
     */
    public function __construct(ArrayEventStore $store, EventQuery $query)
    {
        $this->store = $store;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $query = $this->query;
        $allEvents = $this->store->getEventArray();

        $names = $query->getEventNames();
        $dates = $query->getDateBounds();
        $reverse = $query->isReverse();
        $position = $query->getStartPosition();
        $revision = $query->getStartRevision();
        $aggregateId = $query->hasAggregateId() ? $query->getAggregateId() : null;
        $rootAggregateId = $query->hasRootAggregateId() ? $query->getRootAggregateId() : null;

        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($reverse ? \array_reverse($allEvents) : $allEvents as $event) {

            if ($names && !\in_array($event->getName(), $names)) {
                continue;
            }
            if ($position) {
                if ($reverse) {
                    if ($event->getPosition() > $position) {
                        continue;
                    }
                } else if ($event->getPosition() < $position) {
                    continue;
                }
            }

            if ($revision) {
                if ($reverse) {
                    if ($event->getRevision() > $revision) {
                        continue;
                    }
                } else if ($event->getRevision() < $revision) {
                    continue;
                }
            }

            if ($aggregateId && !$event->getAggregateId()->equals($aggregateId)) {
                continue;
            }
            if ($rootAggregateId && !$event->getRootAggregateId()->equals($rootAggregateId)) {
                continue;
            }

            $eventDate = $event->createdAt();
            if ($dates[1]) {
                if ($eventDate < $dates[0] || $eventDate > $dates[1]) {
                    continue;
                }
            } else if ($dates[0]) {
                if ($reverse) {
                    if ($eventDate > $dates[0]) {
                        continue;
                    }
                } else if ($eventDate < $dates[0]) {
                    continue;
                }
            }

            yield $event;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return 0;
    }
}
