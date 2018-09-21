<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore\Tests;

use MakinaCorpus\EventSourcing\SnapshotStore\Snapshot;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class SnapshotTest extends TestCase
{
    public function testFromArbitraryData()
    {
        $createdAt = new \DateTime('now -22 days');
        $updatedAt = new \DateTime();

        $id = Uuid::uuid4();

        $snapshot = Snapshot::fromArbitraryData($id, 'some_type', 12, $createdAt, $updatedAt, ['some data']);

        $this->assertTrue($id->equals($snapshot->getAggregateId()));
        $this->assertSame('some_type', $snapshot->getAggregateType());
        $this->assertSame(12, $snapshot->getRevision());
        $this->assertEquals($createdAt, $snapshot->createdAt());
        $this->assertEquals($updatedAt, $snapshot->updateAt());
        $this->assertSame(['some data'], $snapshot->getAggregate());
    }
}
