<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

use Ramsey\Uuid\UuidInterface;

/**
 * Array-based in-memory snapshot store reference implementation.
 *
 * It works as the reference implementation, as well as a memory cache ready
 * for production use.
 */
final class MemorySnapshotStore implements SnapshotStore
{
    private $byTypeMap = [];
    private $byIdMap = [];
    private $serializer;

    /**
     * Set serializer
     */
    public function setSerializer(Serializer $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * Get serializer
     */
    private function getSerializer(): Serializer
    {
        return $this->serializer ?? ($this->serializer = new PhpSerializer());
    }

    /**
     * {@inheritdoc}
     */
    public function store(Snapshot $snapshot): void
    {
        $index = (string)$snapshot->getAggregateId();

        $serializer = $this->getSerializer();

        $pleaseStealMyProperties = \Closure::bind(
            function (Snapshot $snapshot) use ($serializer) {
                $stored = clone $snapshot;
                $stored->aggregate = $serializer->unserialize(
                    $serializer->serialize(
                        $snapshot->getAggregate()
                    )
                );

                return $stored;
            },
            $snapshot, Snapshot::class
        );

        $stored = $pleaseStealMyProperties($snapshot);

        $this->byTypeMap[$stored->getAggregateType()][$index] = $stored;
        $this->byIdMap[$index] = $stored;
    }

    /**
     * {@inheritdoc}
     */
    public function storeAll(iterable $snapshots): void
    {
        /** @var \MakinaCorpus\EventSourcing\SnapshotStore\Snapshot $snapshot */
        foreach ($snapshots as $snapshot) {
            $this->store($snapshot);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(UuidInterface $id): void
    {
        $index = (string)$id;

        /** @var \MakinaCorpus\EventSourcing\SnapshotStore\Snapshot $snapshot */
        if ($snapshot = $this->byIdMap[$index] ?? null) {
            unset($this->byTypeMap[$snapshot->getAggregateType()][$index]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWithType(string $aggregateType): void
    {
        if ($snapshosts = $this->byTypeMap[$aggregateType] ?? []) {

            foreach ($snapshosts as $uuidString => $snapshot) {
                unset($this->byIdMap[$uuidString]);
            }

            unset($this->byIdMap[$uuidString]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(): void
    {
        $this->byTypeMap = [];
        $this->byIdMap = [];
    }

    /**
     * {@inheritdoc}
     */
    public function load(UuidInterface $id): ?Snapshot
    {
        return $this->byIdMap[(string)$id] ?? null;
    }
}
