<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

use MakinaCorpus\EventSourcing\Domain\Aggregate;
use Ramsey\Uuid\UuidInterface;

final class Snapshot
{
    private $aggregate;
    private $aggregateId;
    private $aggregateType;
    private $createdAt;
    private $revision;
    private $updatedAt;

    static public function fromArbitraryData(
        UuidInterface $aggregateId,
        string $aggregateType,
        int $revision,
        \DateTimeInterface $createdAt,
        \DateTimeInterface $updatedAt,
        $aggregate
    ): self {
        $ret = new self;
        $ret->aggregateId = $aggregateId;
        $ret->aggregateType = $aggregateType;
        $ret->createdAt = $createdAt;
        $ret->aggregate = $aggregate;
        $ret->revision = $revision;
        $ret->updatedAt = $updatedAt;

        return $ret;
    }

    static public function fromAggregate(Aggregate $aggregate): self
    {
        $ret = new self;
        $ret->aggregate = $aggregate;
        $ret->aggregateId = $aggregate->getId();
        $ret->aggregateType = $aggregate->getType();
        $ret->createdAt = $aggregate->createdAt();
        $ret->revision = $aggregate->getRevision();
        $ret->updatedAt = $aggregate->updatedAt();

        return $ret;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getAggregateId(): UuidInterface
    {
        return $this->aggregateId;
    }

    public function getAggregate()
    {
        return $this->aggregate;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }

    public function createdAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function updateAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
