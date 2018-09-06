<?php

namespace MakinaCorpus\EventSourcing\Domain\Repository;

use MakinaCorpus\EventSourcing\Domain\Aggregate;
use MakinaCorpus\EventSourcing\Domain\Repository;
use MakinaCorpus\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Default aggregate repository interface.
 */
class DefaultRepository implements Repository
{
    private $className;
    private $eventStore;

    /**
     * Default constructor
     */
    public function __construct(string $className, EventStore $eventStore)
    {
        if (!\class_exists($className)) {
            throw new \InvalidArgumentException(\sprintf("Class %s does not exist", $className));
        }
        if (!\is_subclass_of($className, Aggregate::class)) {
            throw new \InvalidArgumentException(\sprintf("Class %s does not extends %s", $className, Aggregate::class));
        }

        $this->className = $className;
        $this->eventStore = $eventStore;

        $this->init();
    }

    /**
     * Called when object is built.
     */
    protected function init()
    {
    }

    /**
     * Create instance from class with given identier
     */
    private function createInstance(UuidInterface $id): Aggregate
    {
        $aggregate = new $this->className($this->eventStore, $id);

        return $aggregate;
    }

    /**
     * Get event store
     *
     * @codeCoverageIgnore
     */
    final protected function getEventStore(): EventStore
    {
        return $this->eventStore;
    }

    /**
     * {@inheritdoc}
     */
    final public function create(): Aggregate
    {
        return $this->createInstance(Uuid::uuid4());
    }

    /**
     * {@inheritdoc}
     */
    final public function load(UuidInterface $id): Aggregate
    {
        $aggregate = $this->createInstance($id);

        $events = $this->eventStore->getEventsFor($id);

        $aggregate->replay($events);

        return $aggregate;
    }
}
