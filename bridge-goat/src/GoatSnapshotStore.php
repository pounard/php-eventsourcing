<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Bridge\Goat;

use Goat\Runner\RunnerInterface;
use MakinaCorpus\EventSourcing\SnapshotStore\Snapshot;
use MakinaCorpus\EventSourcing\SnapshotStore\SnapshotStore;
use MakinaCorpus\EventSourcing\SnapshotStore\SnapshotStoreTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class GoatSnapshotStore implements SnapshotStore
{
    use SnapshotStoreTrait;

    private $runner;
    private $tableName;

    /**
     * Default constructor
     */
    public function __construct(RunnerInterface $runner, string $tableName)
    {
        $this->runner = $runner;
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function store(Snapshot $snapshot): void
    {
        $this->storeAll([$snapshot]);
    }

    /**
     * {@inheritdoc}
     */
    public function storeAll(iterable $snapshots): void
    {
        $count = 0;
        $transaction = null;

        try {
            $transaction = $this->runner->startTransaction()->start();
            $serializer = $this->getSerializer();

            // @todo convert this to a single query with on duplicate key update
            //   for this we need a generic merge or on duplicate key behavior
            //   support in goat (very hard to achieve) or write a custom sql
            //   query per supported backend
            $deleteIn = [];
            $insert = $this->runner->insertValues($this->tableName);

            /** @var \MakinaCorpus\EventSourcing\SnapshotStore\Snapshot $snapshot */
            foreach ($snapshots as $snapshot) {
                $count++;

                $deleteIn[] = $aggregateId = $snapshot->getAggregateId();

                $insert->values([
                    'aggregate_id' => $aggregateId,
                    'aggregate_type' => $snapshot->getAggregateType(),
                    'revision' => $snapshot->getRevision(),
                    'created_at' => $snapshot->createdAt(),
                    'updated_at' => $snapshot->updateAt(),
                    'data' => $serializer->serialize($snapshot->getAggregate()),
                ]);
            }

            if ($count) {
                $this->runner->delete($this->tableName)->condition('aggregate_id', $deleteIn)->execute();
                $insert->execute();

                $transaction->commit();
            } else {
                $transaction->rollback();
            }

        } catch (\Throwable $e) {
            if ($transaction && $transaction->isStarted()) {
                $transaction->rollback();
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(UuidInterface $id): void
    {
        $this->runner->delete($this->tableName)->condition('aggregate_id', $id)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWithType(string $aggregateType): void
    {
        $this->runner->delete($this->tableName)->condition('aggregate_type', $aggregateType)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(): void
    {
        $this->runner->delete($this->tableName)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function load(UuidInterface $id): ?Snapshot
    {
        $row = $this
            ->runner
            ->select($this->tableName)
            ->condition('aggregate_id', $id)
            ->range(1, 0)
            ->execute()
            ->fetch()
        ;

        if ($row) {
            if ($aggregate = $this->serializer->unserialize($row['data'])) {
                return Snapshot::fromArbitraryData(
                    $row['aggregate_id'] instanceof UuidInterface ? $row['aggregate_id'] : Uuid::fromString((string)$row['aggregate_id']),
                    $row['aggregate_type'],
                    $row['revision'],
                    $row['created_at'],
                    $row['updated_at'],
                    $aggregate
                );
            }
        }

        return null;
    }
}
