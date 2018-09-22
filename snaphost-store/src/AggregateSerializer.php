<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

use MakinaCorpus\EventSourcing\Domain\Aggregate;
use MakinaCorpus\EventSourcing\EventStore\EventStore;

/**
 * Nests the real serializer and remove event store references from the
 * aggregates, restores it upon wakeup.
 *
 * @todo rewrite this in a more simple and reliable way
 */
final class AggregateSerializer implements Serializer
{
    private $removeCallback;
    private $serializer;
    private $setCallback;

    public function __construct(EventStore $eventStore, Serializer $serializer)
    {
        $this->eventStore = $eventStore;
        $this->serializer = $serializer;

        $this->removeCallback = \Closure::bind(
            function (Aggregate $aggregate) use ($eventStore) {
                $serialized = clone $aggregate;

                if ($serialized->eventStore !== $eventStore) {
                    throw new \Exception("We can only serialize aggregates for the same event store");
                }

                unset($serialized->eventStore);

                return $serialized;
            },
            null, Aggregate::class
        );

        $this->setCallback = \Closure::bind(
            function (Aggregate $aggregate) use ($eventStore) {
                $aggregate->eventStore = $eventStore;

                return $aggregate;
            },
            null, Aggregate::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($aggregate): string
    {
        if ($aggregate instanceof Aggregate) {
            $aggregate = \call_user_func($this->removeCallback, $aggregate);
        }

        return $this->serializer->serialize($aggregate);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        if (!$aggregate = $this->serializer->unserialize($value)) {
            return null;
        }

        return \call_user_func($this->setCallback, $aggregate);
    }
}
