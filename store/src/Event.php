<?php

namespace MakinaCorpus\EventSourcing;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Event
{
    const NAMESPACE_DEFAULT = 'global';

    /**
     * @var UuidInterface
     */
    private $aggregateId;
    private $createdAt;
    private $data;
    private $isPublished = false;
    private $name;
    private $namespace = Event::NAMESPACE_DEFAULT;
    private $position = 0;
    private $revision = 0;
    private $rootAggregateId;

    /**
     * Create event from name
     */
    final private static function createInstanceFromName(string $name): self
    {
        if (!$name) {
            throw new \InvalidArgumentException("Event name cannot be empty");
        }

        if (\class_exists($name)) {
            $ret = new $name();
            if (!$ret instanceof Event) {
                // This basic downgrade test is voluntarily loose, this is one of the weak
                // points of this API: it will not, not even in debug mode, advertise to the
                // API user he might have used a wrong class name, except if namespace
                // separators are present within the name. Although, we cannot allow it to
                // break, else loading outdated or broken data would be not possible, and
                // counterfact the initial goal of event sourcing, which is allowing to
                // re-intrepret the past without modifying it.
                if (false !== \strpos($name, '\\')) {
                    \trigger_error(\sprintf("Class with name '%s' exists, but does not extend %s, did you forget an extend statement?", $name, Event::class), E_USER_WARNING);
                }
                $ret = new static();
            }
        } else {
            $ret = new static();
        }

        return $ret;
    }

    /**
     * Create from event store data
     */
    final public static function fromEventStore(
        string $namespace,
        int $position,
        UuidInterface $aggregateId,
        int $revision,
        $rootAggregateId,
        \DateTimeInterface $createdAt,
        string $name,
        array $data,
        bool $isPublished
    ): self {

        $ret = self::createInstanceFromName($name);
        $ret->aggregateId = $aggregateId;
        $ret->createdAt = $createdAt;
        $ret->data = $data;
        $ret->isPublished = $isPublished;
        $ret->name = \get_class($ret);
        $ret->namespace = $namespace;
        $ret->position = $position;
        $ret->revision = $revision;
        $ret->rootAggregateId = $rootAggregateId;

        return $ret;
    }

    /**
     * Create event for aggregate
     */
    final public static function createFor(string $name, UuidInterface $aggregateId, array $data = [], UuidInterface $rootAggregateId = null): self
    {
        $ret = self::createInstanceFromName($name);
        $ret->aggregateId = $aggregateId;
        $ret->data = $data;
        $ret->name = \get_class($ret);
        $ret->rootAggregateId = $rootAggregateId;

        return $ret;
    }

    final public static function create(string $name, array $data = []): self
    {
        $ret = self::createInstanceFromName($name);
        $ret->aggregateId = Uuid::uuid4();
        $ret->data = $data;
        $ret->name = $name ?? \get_class($ret);

        return $ret;
    }

    /**
     * Get event namespace, should use for debugging purpose only
     */
    final public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Get position in the whole namespace
     */
    final public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Get aggregate identifier
     */
    final public function getAggregateId(): UuidInterface
    {
        return $this->aggregateId;
    }

    /**
     * Get revision for the aggregate
     */
    final public function getRevision(): int
    {
        return $this->revision;
    }

    /**
     * Has this event a root aggregate?
     */
    final public function hasRootAggregate(): bool
    {
        return $this->rootAggregateId && !$this->aggregateId->equals($this->rootAggregateId);
    }

    /**
     * Get root aggregate identifier, or self identifier if none
     */
    final public function getRootAggregateId(): UuidInterface
    {
        return $this->rootAggregateId ?? $this->aggregateId;
    }

    /**
     * Get event name (the class name)
     */
    final public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get creation date
     */
    final public function createdAt(): \DateTimeInterface
    {
        return $this->createdAt ?? ($this->createdAt = new \DateTimeImmutable());
    }

    /**
     * Is this event persisted
     */
    final public function isStored(): bool
    {
        return $this->revision !== 0;
    }

    /**
     * Is this event published
     */
    final public function isPublished(): bool
    {
        return $this->isPublished;
    }

    /**
     * Get event data
     */
    final public function getData(): array
    {
        return $this->data ?? [];
    }
}
