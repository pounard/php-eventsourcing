<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore;

/**
 * In memory event stream, use this for recording.
 */
final class MemoryEventStream implements \IteratorAggregate, EventStream
{
    private $events = [];
    private $readonly = false;

    /**
     * Default constructor
     */
    public function __construct(iterable $events, bool $readonly = true)
    {
        $this->readonly = $readonly;
        if ($events) {
            $this->events = \is_array($events) ? $events : \iterator_to_array($events);
        }
    }

    /**
     * Append event to this memory stream
     */
    public function push(Event $event)
    {
        if ($this->readonly) {
            throw new \BadMethodCallException(\sprintf("Event stream is readonly, you cannot push events"));
        }

        $this->events[] = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->events as $event) {
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
