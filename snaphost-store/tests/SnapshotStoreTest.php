<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore\Tests;

use MakinaCorpus\EventSourcing\Domain\Repository\DefaultRepository;
use MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity;
use MakinaCorpus\EventSourcing\EventStore\ArrayEventStoreFactory;
use MakinaCorpus\EventSourcing\EventStore\Event;
use MakinaCorpus\EventSourcing\EventStore\EventStore;
use MakinaCorpus\EventSourcing\SnapshotStore\Snapshot;
use MakinaCorpus\EventSourcing\SnapshotStore\SnapshotStore;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

abstract class SnapshotStoreTest extends TestCase
{
    /**
     * Create a snapshot store for functionnal and unit tests
     */
    abstract protected function getSnapshotStore(): SnapshotStore;

    /**
     * Create an event store for functional tests
     */
    protected function getEventStore(): EventStore
    {
        return (new ArrayEventStoreFactory())->getEventStore(\uniqid('test-'));
    }

    /**
     * @return string[]
     */
    private function getRandomTypes(): array
    {
        return [Event::TYPE_DEFAULT, 'article', 'project', 'user', 'event'];
    }

    /**
     * Creates a random set of snapshots
     */
    private function createSomeSnapshots(int $count = 50)
    {
        $types = $this->getRandomTypes();

        for ($i = 0; $i < $count; ++$i) {
            $id = Uuid::uuid4();

            $type = $types[\rand(0, count($types) - 1)];
            $createdAt = new \DateTime(sprintf('now -%d days', \rand(50, 100)));
            $updatedAt = new \DateTime(sprintf('now -%d days', \rand(1, 49)));

            yield (string)$id => Snapshot::fromArbitraryData($id, $type, rand(1, 27), $createdAt, $updatedAt, ['Random data']);
        }
    }

    public function testFunctionnalScenario()
    {
        $store = $this->getSnapshotStore();

        $repository = new DefaultRepository();
        $repository->setEventStore($eventStore = $this->getEventStore());
        $repository->setClassName(MockAggregateEntity::class);

        // @todo:
        //  - create an aggregate with a store
        //  - raise events on it
        //  - store it
        //  - load the aggregate and compare property per property
        //  - raise some other events
        //  - compare the aggregate and check it differs at least revision and update date
        //  - BEWARE that memory store may keep references, it *MUST* be serialized or cloned

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity $aggregate */
        $aggregate = $repository->create();

        // Store the initial entity version
        $store->store(Snapshot::fromAggregate($aggregate));

        $snapshot = $store->load($aggregate->getId());

        // Aggregate MUST be a copy, but everything must be equal
        $this->assertNotSame($loaded = $snapshot->getAggregate(), $aggregate);
        $this->assertTrue($snapshot->getAggregateId()->equals($loaded->getId()));
        $this->assertSame($aggregate->getType(), $snapshot->getAggregateType());
        $this->assertSame($aggregate->getRevision(), $snapshot->getRevision());
        $this->assertEquals($aggregate->createdAt(), $snapshot->createdAt());
        $this->assertEquals($aggregate->updatedAt(), $snapshot->updateAt());

        // Raise a new event over the aggregate
        $aggregate->updateWith(['foo' => 1245]);

        $loaded = $store->load($aggregate->getId());
        $this->assertTrue($loaded->getAggregateId()->equals($aggregate->getId()));
        $this->assertSame($aggregate->getType(), $loaded->getAggregateType());
        $this->assertLessThan($aggregate->getRevision(), $loaded->getRevision());
    }

    public function testDeleteAll()
    {
        $store = $this->getSnapshotStore();

        $list = \iterator_to_array($this->createSomeSnapshots());

        $store->storeAll($list);
        $store->deleteAll();

        /** @var \MakinaCorpus\EventSourcing\SnapshotStore\Snapshot $snapshot */
        foreach ($list as $snapshot) {
            $this->assertNull($store->load($snapshot->getAggregateId()));
        }
    }

    public function testDeleteByType()
    {
        $store = $this->getSnapshotStore();

        $list = \iterator_to_array($this->createSomeSnapshots());

        $store->storeAll($list);

        $deletedTypes = ['project', 'user'];
        foreach ($deletedTypes as $type) {
            $store->deleteWithType($type);
        }

        $count = 0;
        $misses = 0;

        /** @var \MakinaCorpus\EventSourcing\SnapshotStore\Snapshot $snapshot */
        foreach ($list as $snapshot) {
            if (\in_array($snapshot->getAggregateType(), $deletedTypes)) {
                $this->assertNull($store->load($snapshot->getAggregateId()));
                $misses++;
            } else {
                $this->assertNotNull($store->load($snapshot->getAggregateId()));
                $count++;
            }
        }

        // Statically improbable that all items are from deleted types
        $this->assertGreaterThan(0, $count);
        $this->assertGreaterThan(0, $misses);
    }

    public function testStore()
    {
        $store = $this->getSnapshotStore();

        $list = \iterator_to_array($this->createSomeSnapshots());

        foreach ($list as $snapshot) {
            $store->store($snapshot);
        }

        /** @var \MakinaCorpus\EventSourcing\SnapshotStore\Snapshot $snapshot */
        foreach ($list as $snapshot) {
            $this->assertNotNull($loaded = $store->load($snapshot->getAggregateId()));

            $this->assertTrue($loaded->getAggregateId()->equals($snapshot->getAggregateId()));
            $this->assertSame($snapshot->getAggregateType(), $loaded->getAggregateType());
        }
    }
}
