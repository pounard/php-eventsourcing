<?php

namespace MakinaCorpus\EventSourcing\Tests;

use PHPUnit\Framework\TestCase;
use MakinaCorpus\EventSourcing\Event;
use Ramsey\Uuid\Uuid;

/**
 * All other event class methods are implicitely tested along other tests, this test
 * case is only meant to test edge cases.
 */
class EventTest extends TestCase
{
    public function testCreationFromArbitraryName()
    {
        $event = Event::create('arbitrary_name');
        $this->assertSame('arbitrary_name', $event->getName());
        $this->assertSame(Event::class, \get_class($event));
    }

    public function testCreationFromEventClass()
    {
        $event = Event::create(EventThatInherits::class);
        $this->assertSame(EventThatInherits::class, $event->getName());
        $this->assertSame(EventThatInherits::class, \get_class($event));
    }

    public function testCreationFromNonEventClass()
    {
        $event = @Event::create(EventWithBrokenClass::class);
        $this->assertSame(EventWithBrokenClass::class, $event->getName());
        $this->assertNotSame(EventWithBrokenClass::class, \get_class($event));
        $this->assertSame(Event::class, \get_class($event));

        $lastError = \error_get_last();
        $this->assertNotEmpty($lastError);
        $this->assertArrayHasKey('message', $lastError);
        $this->assertContains('does not extend', $lastError['message']);
    }

    public function testRootAggregate()
    {
        $aggregateId = Uuid::uuid4();
        $otherAggregateId = Uuid::uuid4();

        $event = Event::createFor('some_name', $aggregateId);
        $this->assertFalse($event->hasRootAggregate());

        // Using the same UUID, it should consider it doesn't have a root aggregate identifier
        $event = Event::createFor('some_name', $aggregateId, [], $aggregateId);
        $this->assertFalse($event->hasRootAggregate());

        // In other hand, with a different, it should check
        $event = Event::createFor('some_name', $aggregateId, [], $otherAggregateId);
        $this->assertTrue($event->hasRootAggregate());
    }
}
