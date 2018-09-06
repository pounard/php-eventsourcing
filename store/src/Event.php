<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Event
{
    const NAMESPACE_DEFAULT = 'global';
    const TYPE_DEFAULT = 'none';

    /**
     * @var UuidInterface
     */
    private $aggregateId;
    private $aggregateType;
    private $createdAt;
    private $data;
    private $isPublished = false;
    private $name;
    private $namespace = Event::NAMESPACE_DEFAULT;
    private $position = 0;
    private $revision = 0;

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

        $ret->name = $name;

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
        $aggregateType,
        \DateTimeInterface $createdAt,
        string $name,
        array $data,
        bool $isPublished
    ): self {

        $ret = self::createInstanceFromName($name);
        $ret->aggregateId = $aggregateId;
        $ret->aggregateType = $aggregateType;
        $ret->createdAt = $createdAt;
        $ret->data = $data;
        $ret->isPublished = $isPublished;
        $ret->name = $name;
        $ret->namespace = $namespace;
        $ret->position = $position;
        $ret->revision = $revision;

        return $ret;
    }

    /**
     * Create event for aggregate
     */
    final public static function createFor(string $name, UuidInterface $aggregateId, array $data = [], string $aggregateType = null): self
    {
        $ret = self::createInstanceFromName($name);
        $ret->aggregateId = $aggregateId;
        $ret->aggregateType = $aggregateType;
        $ret->data = $data;

        return $ret;
    }

    final protected static function createWithClassFor(UuidInterface $aggregateId, array $data = [], string $aggregateType = null): self
    {
        $ret = new static();
        $ret->aggregateId = Uuid::uuid4();
        $ret->aggregateType = $aggregateType;
        $ret->data = $data;
        $ret->name = \get_class($ret);

        return $ret;
    }

    final protected static function createWithClass(array $data = [], string $aggregateType = null): self
    {
        $ret = new static();
        $ret->aggregateId = Uuid::uuid4();
        $ret->aggregateType = $aggregateType;
        $ret->data = $data;
        $ret->name = \get_class($ret);

        return $ret;
    }

    final public static function create(string $name, array $data = [], string $aggregateType = null): self
    {
        $ret = self::createInstanceFromName($name);
        $ret->aggregateId = Uuid::uuid4();
        $ret->aggregateType = $aggregateType;
        $ret->data = $data;
        $ret->name = $name;

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
     * Has this event an aggregate type?
     */
    final public function hasAggregateType(): bool
    {
        return $this->aggregateType && self::TYPE_DEFAULT !== $this->aggregateType;
    }

    /**
     * Get aggregate type
     */
    final public function getAggregateType(): string
    {
        return $this->aggregateType ?? self::TYPE_DEFAULT;
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
     * Get value from data
     */
    final public function get(string $name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }

    /**
     * Get event data
     */
    final public function getData(): array
    {
        return $this->data ?? [];
    }
}
