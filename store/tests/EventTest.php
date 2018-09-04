<?php

namespace MakinaCorpus\EventSourcing\EventStore\Tests;

use MakinaCorpus\EventSourcing\EventStore\Event;
use PHPUnit\Framework\TestCase;

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
}
