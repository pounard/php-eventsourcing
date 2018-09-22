<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

use Ramsey\Uuid\UuidInterface;

interface SnapshotStore
{
    /**
     * Store aggregate snapshot
     */
    public function store(Snapshot $snapshot): void;

    /**
     * Store a set of agggregate snapshots
     *
     * @param Snapshot[] $snapshots
     */
    public function storeAll(iterable $snapshots): void;

    /**
     * Delete aggregate snapshot
     */
    public function delete(UuidInterface $id): void;

    /**
     * Delete all stored snapshots with given type
     */
    public function deleteWithType(string $aggregateType): void;

    /**
     * Delete all stored snapshots
     */
    public function deleteAll(): void;

    /**
     * Load aggregate snapshot
     */
    public function load(UuidInterface $id): ?Snapshot;

    /**
     * Set serializer
     *
     * @todo This should not be part of the public API
     */
    public function setSerializer(Serializer $serializer): void;

    /**
     * Get serializer
     *
     * @todo This should not be part of the public API
     */
    public function getSerializer(): Serializer;
}
