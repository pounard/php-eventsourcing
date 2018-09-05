<?php

namespace MakinaCorpus\EventSourcing\Domain\Tests;

use MakinaCorpus\EventSourcing\Domain\Repository;
use MakinaCorpus\EventSourcing\Domain\RepositoryFactory;
use MakinaCorpus\EventSourcing\EventStore\ArrayEventStoreFactory;
use PHPUnit\Framework\TestCase;
use MakinaCorpus\EventSourcing\EventStore\Event;

/**
 * All other query class methods are implicitely tested along other tests, this test
 * case is only meant to test edge cases.
 */
class AggregateTest extends TestCase
{
    private function createRepository(string $aggregateType): Repository
    {
        return (new RepositoryFactory(new ArrayEventStoreFactory()))->getRepository($aggregateType);
    }

    public function testBasicFunctionnality()
    {
        $repository = $this->createRepository(MockAggregate::class);

        $aggregate = $repository->create();

        $this->assertInstanceOf(MockAggregate::class, $aggregate);
        $this->assertNotEmpty($aggregate->createdAt());
        $this->assertEquals($aggregate->createdAt(), $aggregate->updatedAt());
        $this->assertNotEmpty($aggregate->getId());
        $this->assertSame(0, $aggregate->getRevision());
        $this->assertTrue($aggregate->isNew());

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregate $aggregate */
        $called = false;
        $aggregate->addWhenHandler('FooEvent', function (Event $event) use (&$called) {
            $called = true;
        });

        $aggregate->raiseArbitraryEvent('FooEvent', []);
        $this->assertTrue($called);
        $this->assertGreaterThan($aggregate->createdAt(), $aggregate->updatedAt());
    }
}
