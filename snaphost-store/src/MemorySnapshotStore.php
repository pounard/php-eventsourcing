<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

use Ramsey\Uuid\UuidInterface;

/**
 * Array-based in-memory snapshot store reference implementation.
 *
 * It works as the reference implementation, as well as a memory cache ready
 * for production use.
 *
 * @todo
 *   - make unserialize/serialize more performant by creating the closure
 *     only once and,
 *   - make a snapshot based repository, or make it within the default
 *     implementation so that anyone implementing it will have it
 *     (and make it optionnal using a setter),
 *   - write redis implementation (because I love Redis),
 *   - plug it into the symfony bridge per default, and add configuration
 *     for it,
 *   - make it listen to event store events, for automatic aggregate
 *     saving, find a way, because event store does not know anything
 *     about aggregates,
 *   - implement the event store event dispatcher and events, and make
 *     sure that aggregate (in domain api) based code attach aggregates
 *     to event for this to catch and save them,
 *   - write more tests,
 *   - go sleep and stop listening to portal 2 ost.
 */
final class MemorySnapshotStore implements SnapshotStore
{
    use SnapshotStoreTrait;

    private $byTypeMap = [];
    private $byIdMap = [];

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
