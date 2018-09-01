<?php

namespace MakinaCorpus\EventSourcing\Tests;

use MakinaCorpus\EventSourcing\ConcretEventQuery;
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
        $query = new ConcretEventQuery();
        $query->fromDate(new \DateTime('1983-05-12'));

        $this->assertEquals([new \DateTime('1983-05-12'), null], $query->getDateBounds());
    }

    public function testDateBounds()
    {
        // First use case: call between before from
        $query = new ConcretEventQuery();
        $query->betweenDates(new \DateTime('1983-03-22'), new \DateTime('2018-08-31'));

        @$query->fromDate(new \DateTime('1983-05-12'));
        $lastError = \error_get_last();
        $this->assertNotEmpty($lastError);
        $this->assertArrayHasKey('message', $lastError);
        $this->assertContains('call is ignored', $lastError['message']);

        $this->assertEquals([new \DateTime('1983-03-22'), new \DateTime('2018-08-31')], $query->getDateBounds());

        // Second use case: call from before between
        $query = new ConcretEventQuery();
        $query->fromDate(new \DateTime('1983-05-12'));
        // Also tests that reverse order of dates is put in the right order
        @$query->betweenDates(new \DateTime('2018-08-31'), new \DateTime('1983-03-22'));
        $lastError = \error_get_last();
        $this->assertNotEmpty($lastError);
        $this->assertArrayHasKey('message', $lastError);
        $this->assertContains('call overrides', $lastError['message']);

        $this->assertEquals([new \DateTime('1983-03-22'), new \DateTime('2018-08-31')], $query->getDateBounds());
    }

    public function testAggregateAndRootAggregateCanBeString()
    {
        $query = new ConcretEventQuery();
        $aggregateId = Uuid::uuid4();
        $rootAggregateId = Uuid::uuid4();

        $query->for((string)$aggregateId);
        $this->assertTrue($aggregateId->equals($query->getAggregateId()));

        $query->withRoot((string)$rootAggregateId);
        $this->assertTrue($rootAggregateId->equals($query->getRootAggregateId()));
    }

    public function testAggregateAccessor()
    {
        $query = new ConcretEventQuery();
        $this->assertFalse($query->hasAggregateId());
        $query->for($reference = Uuid::uuid4());
        $this->assertTrue($query->hasAggregateId());
        $this->assertSame($reference, $query->getAggregateId());

        $query = new ConcretEventQuery();
        $this->expectExceptionMessageRegExp('/has no aggregate/');
        $query->getAggregateId();
    }

    public function testRootAggregateAccessor()
    {
        $query = new ConcretEventQuery();
        $this->assertFalse($query->hasRootAggregateId());
        $query->withRoot($reference = Uuid::uuid4());
        $this->assertTrue($query->hasRootAggregateId());
        $this->assertSame($reference, $query->getRootAggregateId());

        $query = new ConcretEventQuery();
        $this->expectExceptionMessageRegExp('/has no root aggregate/');
        $query->getRootAggregateId();
    }
}
