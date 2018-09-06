<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain;

use MakinaCorpus\EventSourcing\EventStore\Event;
use MakinaCorpus\EventSourcing\EventStore\EventStore;
use MakinaCorpus\EventSourcing\EventStore\EventStream;
use Ramsey\Uuid\UuidInterface;

/**
 * Your domain model objects should extend this class in order to be storable
 * into an event sourcing event store.
 */
abstract class Aggregate
{
    private $aggregateId;
    private $createdAt;
    private $eventStore;
    private $revision = 0;
    private $updatedAt;

    /**
     * Normalize event or event class name
     */
    private static function normalizeName(string $name): string
    {
        // @todo this is ugly
        return \implode('', \array_map('ucfirst', \preg_split('/[^a-zA-Z1-9]+/', $name)));
    }

    /**
     * Get aggregate type, this is valid to override
     */
    public static function getType(): string
    {
        return \get_called_class();
    }

    /**
     * Default constructor, this MUST NEVER be called manually
     */
    final public function __construct(EventStore $eventStore, UuidInterface $aggregateId)
    {
        $this->eventStore = $eventStore;
        $this->aggregateId = $aggregateId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    /**
     * Is this
     */
    final public function isNew(): bool
    {
        return !$this->revision;
    }

    /**
     * Get aggregate identifier
     */
    final public function getId(): UuidInterface
    {
        return $this->aggregateId;
    }

    /**
     * Get aggregate revision
     */
    final public function getRevision(): int
    {
        return $this->revision;
    }

    /**
     * Get aggregate creation date
     */
    final public function createdAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Get aggregate latest update date
     */
    final public function updatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Event happens on the object, use this to record changes.
     *
     * Returned event is an immutable instance that matches the database
     * state, with all identifiers and date set.
     */
    final protected function eventOccurs(Event $event): Event
    {
        $this->play($event);

        return $this->eventStore->store($event);
    }

    /**
     * Event happens on the object, use this to record changes.
     *
     * Returned event is an immutable instance that matches the database
     * state, with all identifiers and date set.
     */
    final protected function occurs(string $name, array $data = []): Event
    {
        $event = Event::createFor($name, $this->aggregateId, $data, $this->getType());

        return $this->eventOccurs($event);
    }

    /**
     * Override this method if you wish to implement a custom global when() method.
     *
     * This is probably not a good idea, but you may still implement it the way you
     * like it - this is your saviour if you want your code to be messy.
     *
     * @return bool
     *   True if you consumed and handled the event, false otherwise.
     */
    protected function when(Event $event): bool
    {
        return false;
    }

    /**
     * Play single event
     */
    private function play(Event $event)
    {
        $eventName = $event->getName();
        $methodName = 'when'.self::normalizeName($eventName);

        if (\method_exists($this, $methodName)) {
            \call_user_func([$this, $methodName], $event);
        } else if (!$this->when($event)) {
            // Provide a fallback with a more generic when() method.
            throw new \RuntimeException(\sprintf("Method %s() is missing on class %s or when() method did not properly the event '%s'", $methodName, \get_class($this), $eventName));
        }

        $this->revision = $event->getRevision();
        $this->updatedAt = $event->createdAt();
        $this->createdAt = $this->createdAt ?? $this->updatedAt;
    }

    /**
     * Given an event stream, build the object representation
     */
    final public function replay(EventStream $events)
    {
        /** @var \MakinaCorpus\EventSourcing\EventStore\Event $event */
        foreach ($events as $event) {
            $this->play($event);
        }
    }
}
