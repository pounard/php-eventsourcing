<?php

namespace MakinaCorpus\EventSourcing\Domain\Tests;

use MakinaCorpus\EventSourcing\Domain\Repository;
use MakinaCorpus\EventSourcing\Domain\RepositoryFactory;
use MakinaCorpus\EventSourcing\EventStore\ArrayEventStoreFactory;
use MakinaCorpus\EventSourcing\EventStore\Event;
use PHPUnit\Framework\TestCase;

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

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregate $aggregate */
        $aggregate = $repository->create();

        $this->assertInstanceOf(MockAggregate::class, $aggregate);
        $this->assertNotEmpty($aggregate->createdAt());
        $this->assertEquals($aggregate->createdAt(), $aggregate->updatedAt());
        $this->assertNotEmpty($aggregate->getId());
        $this->assertSame(0, $aggregate->getRevision());
        $this->assertTrue($aggregate->isNew());
    }

    public function testGenericWhenMethod()
    {
        $repository = $this->createRepository(MockAggregate::class);

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregate $aggregate */
        $aggregate = $repository->create();

        $called = false;
        $aggregate->addWhenHandler('FooEvent', function (Event $event) use (&$called) {
            $called = true;
        });

        $aggregate->raiseArbitraryEvent('FooEvent', []);
        $this->assertTrue($called);
    }

    public function testEventUpdatesDate()
    {
        $repository = $this->createRepository(MockAggregate::class);

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregate $aggregate */
        $aggregate = $repository->create();

        $this->assertEquals($aggregate->createdAt(), $aggregate->updatedAt());

        $aggregate->addWhenHandler('FooEvent', function (Event $event) {});
        $aggregate->raiseArbitraryEvent('FooEvent', []);

        $this->assertGreaterThan($aggregate->createdAt(), $aggregate->updatedAt());
    }

    public function testWhenMethodFailsWithNoImplementation()
    {
        $repository = $this->createRepository(MockAggregateWithMethods::class);

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateWithMethods $aggregate */
        $aggregate = $repository->create();

        $func = \Closure::bind(function () {
            $this->occurs('FooEvent', []);
        }, $aggregate, MockAggregateWithMethods::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/Method .* is missing on class .* or when/');
        $func();
    }

    public function testCustomWhenHandlersAndWhenMethodNamingConvention()
    {
        $repository = $this->createRepository(MockAggregateWithMethods::class);

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateWithMethods $aggregate */
        $aggregate = $repository->create();

        $aggregate->somethingHappens();
        $this->assertSame('SomethingHappened', $aggregate->getLastEventName());

        $aggregate->someOtherThingHappens();
        $this->assertSame('some_other_thing_happened', $aggregate->getLastEventName());
    }

    public function testReplay()
    {
        $repository = $this->createRepository(MockAggregateWithMethods::class);

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateWithMethods $aggregate */
        $aggregate = $repository->create();
        $aggregate->somethingHappens();
        $aggregate->someOtherThingHappens();
        $aggregate->somethingHappens();

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateWithMethods $replayedAggregate */
        $replayedAggregate = $repository->load($aggregate->getId());
        $this->assertNotSame($aggregate, $replayedAggregate);
        $this->assertSame(3, $replayedAggregate->getRevision());
        $this->assertSame('SomethingHappened', $replayedAggregate->getLastEventName());
    }
}
