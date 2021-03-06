<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore\Tests;

use MakinaCorpus\EventSourcing\EventStore\ConcreteEventQuery;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * All other query class methods are implicitely tested along other tests, this test
 * case is only meant to test edge cases.
 */
class EventQueryTest extends TestCase
{
    public function testSingleDate()
    {
        $query = new ConcreteEventQuery();
        $query->fromDate(new \DateTime('1983-05-12'));

        $this->assertEquals([new \DateTime('1983-05-12'), null], $query->getDateBounds());
    }

    public function testDateBounds()
    {
        // First use case: call between before from
        $query = new ConcreteEventQuery();
        $query->betweenDates(new \DateTime('1983-03-22'), new \DateTime('2018-08-31'));

        @$query->fromDate(new \DateTime('1983-05-12'));
        $lastError = \error_get_last();
        $this->assertNotEmpty($lastError);
        $this->assertArrayHasKey('message', $lastError);
        $this->assertContains('call is ignored', $lastError['message']);

        $this->assertEquals([new \DateTime('1983-03-22'), new \DateTime('2018-08-31')], $query->getDateBounds());

        // Second use case: call from before between
        $query = new ConcreteEventQuery();
        $query->fromDate(new \DateTime('1983-05-12'));
        // Also tests that reverse order of dates is put in the right order
        @$query->betweenDates(new \DateTime('2018-08-31'), new \DateTime('1983-03-22'));
        $lastError = \error_get_last();
        $this->assertNotEmpty($lastError);
        $this->assertArrayHasKey('message', $lastError);
        $this->assertContains('call overrides', $lastError['message']);

        $this->assertEquals([new \DateTime('1983-03-22'), new \DateTime('2018-08-31')], $query->getDateBounds());
    }

    public function testAggregateCanBeString()
    {
        $query = new ConcreteEventQuery();
        $aggregateId = Uuid::uuid4();

        $query->for((string)$aggregateId);
        $this->assertTrue($aggregateId->equals($query->getAggregateId()));
    }

    public function testAggregateAccessor()
    {
        $query = new ConcreteEventQuery();
        $this->assertFalse($query->hasAggregateId());
        $query->for($reference = Uuid::uuid4());
        $this->assertTrue($query->hasAggregateId());
        $this->assertSame($reference, $query->getAggregateId());

        $query = new ConcreteEventQuery();
        $this->expectExceptionMessageRegExp('/has no aggregate/');
        $query->getAggregateId();
    }
}
