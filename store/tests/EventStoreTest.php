<?php

namespace MakinaCorpus\EventSourcing\Tests;

use MakinaCorpus\EventSourcing\Event;
use MakinaCorpus\EventSourcing\EventStore;
use PHPUnit\Framework\TestCase;

/**
 * Tests the views
 */
abstract class EventStoreTest extends TestCase
{
    /**
     * This is a data provider, it MUST create an array, which contains
     * arrays, each array being an EventStore implementation. It can be
     * a generator.
     */
    abstract public function getEventStore();

    /**
     * @dataProvider getEventStore
     */
    public function testStoreReturn(EventStore $store)
    {
        $userEvent = Event::create('event', ['foo' => 'bar']);
        $this->assertFalse($userEvent->isStored());
        $this->assertNotEmpty($uuid = $userEvent->getAggregateId());
        $this->assertSame((string)$uuid, (string)$userEvent->getRootAggregateId());
        $this->assertFalse($userEvent->isPublished());
        $this->assertEmpty($userEvent->getPosition());
        $this->assertEmpty($userEvent->getRevision());

        $event = $store->store($userEvent);
        $this->assertNotSame($event, $userEvent);
        $this->assertGreaterThanOrEqual(1, $event->getPosition());
        $this->assertGreaterThanOrEqual(1, $event->getRevision());
        $this->assertSame($store->getNamespace(), $event->getNamespace());
        $this->assertFalse($event->isPublished());
        $this->assertLessThan(new \DateTimeImmutable(), $event->createdAt());
        $this->assertSame(Event::class, $event->getName());
        $this->assertNotEmpty($uuid = $event->getAggregateId());
        $this->assertTrue($event->isStored());
        $this->assertSame((string)$uuid, (string)$event->getRootAggregateId());
    }

    /**
     * Get the number of events to generate for each test.
     *
     * Beware that the more you generate, the more complete will be the tests, but slower
     * they will be as well, this leave the possibility for each driver to set this to a
     * sensible value where tests will run fast enough.
     */
    protected function getDefaultCount(): int
    {
        return 200;
    }

    /**
     * @return string[]
     */
    private function getRandomNames(): array
    {
        return [EventThatInherits::class, 'foo', 'bar', 'baz', 'some', 'other', 'cassoulet'];
    }

    /**
     * @return \Ramsey\Uuid\UuidInterface[]
     */
    private function generateLotsAndLotsOfEvents(EventStore $store, \DateTime $startDate, array $names, $count, $moreRoots = false): array
    {
        $max = count($names) - 1;

        $setDate = \Closure::bind(
            function (Event $event, \DateTimeInterface $date) {
                $event->createdAt = clone $date;
            },
            null, Event::class
        );

        $aggregates = [];
        // RDBMS based stores might have serial starting from non-zero values.
        $minPos = null;
        $maxPos = null;

        for ($i = 0; $i < $count; ++$i) {

            $name = $names[\rand(0, $max)];
            $addAttr = rand(0, 10) < 3; // 30% chances too
            $startDate = $startDate->add(new \DateInterval("PT10M")); // +10 min each event

            if ($moreRoots) {
                $isNew = !$aggregates || rand(0, 100) < 10; // 10% chances
                $withRoot = $aggregates && rand(0, 100) < 90; // 90% chances
            } else {
                $isNew = !$aggregates || rand(0, 100) < 30; // 30% chances
                $withRoot = $aggregates && rand(0, 100) < 30; // 30% chances
            }

            $data = [];
            if ($addAttr) {
                $data['name_was'] = $name;
            }

            if ($isNew) {
                $event = Event::create($name, $data);
                $aggregates[] = $event->getAggregateId();
            } else {
                $aggregateId = $aggregates[rand(0, count($aggregates) - 1)];
                if ($withRoot) {
                    $rootAggregateId = $aggregates[rand(0, count($aggregates) - 1)];
                    $event = Event::createFor($name, $rootAggregateId, $data);
                } else {
                    $event = Event::createFor($name, $aggregateId, $data);
                }
            }

            $setDate($event, $startDate); // Force date
            $currentPosition = $store->store($event)->getPosition();

            if (!$minPos) {
                $minPos = $maxPos = $currentPosition;
            } else {
                $minPos = min($minPos, $currentPosition);
                $maxPos = max($maxPos, $currentPosition);
            }
        }

        return [$minPos, $maxPos, $aggregates];
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryByName(EventStore $store)
    {
        $count = $this->getDefaultCount();
        $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count);

        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->withName(EventThatInherits::class)) as $event) {
            $this->assertInstanceOf(EventThatInherits::class, $event);
        }
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryFromDate(EventStore $store)
    {
        $count = $this->getDefaultCount();
        $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count);

        $reference = new \DateTime('now +50 minute');

        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->fromDate($reference)) as $event) {
            $this->assertGreaterThan($reference, $event->createdAt());
        }

        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->fromDate($reference)->reverse()) as $event) {
            $this->assertLessThan($reference, $event->createdAt());
        }
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryWithDateBounds(EventStore $store)
    {
        $count = $this->getDefaultCount();
        $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count);

        $from = new \DateTime('now +50 minute');
        $to = new \DateTime('now 2 hour');

        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->betweenDates($from, $to)) as $event) {
            $this->assertGreaterThan($from, $event->createdAt());
            $this->assertLessThan($to, $event->createdAt());
        }

        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->betweenDates($to, $from)->reverse()) as $event) {
            $this->assertGreaterThan($from, $event->createdAt());
            $this->assertLessThan($to, $event->createdAt());
        }
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryByAggregate(EventStore $store)
    {
        $count = $this->getDefaultCount();
        list(,, $aggregates) = $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count);

        // This can only work if we have one or more aggregates
        $this->assertGreaterThan(1, count($aggregates));

        /** @var \Ramsey\Uuid\UuidInterface $aggregateId */
        $aggregateId = $aggregates[rand(0, count($aggregates) - 1)];

        /** @var \MakinaCorpus\EventSourcing\Event $event */
        $total = 0;
        foreach ($store->getEventsWith($store->createQuery()->for($aggregateId)) as $event) {
            $this->assertTrue($aggregateId->equals($event->getAggregateId()));
            $total++;
        }
        $this->assertLessThan($count, $total);
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryByRootAggregate(EventStore $store)
    {
        $count = $this->getDefaultCount();
        list(,, $aggregates) = $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count);

        // This can only work if we have one or more aggregates
        $this->assertGreaterThan(1, count($aggregates));

        /** @var \Ramsey\Uuid\UuidInterface $aggregateId */
        $aggregateId = $aggregates[rand(0, count($aggregates) - 1)];

        $total = 0;
        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->withRoot($aggregateId)) as $event) {
            $this->assertTrue($aggregateId->equals($event->getRootAggregateId()));
            $total++;
        }
        $this->assertLessThan($count, $total);
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryByPosition(EventStore $store)
    {
        $count = $this->getDefaultCount();
        list($min, $max) = $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count);
        $startWith = (int)($min + floor(($max - $min) / 2));

        $first = null;
        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->fromPosition($startWith)) as $event) {
            if (!$first) {
                $first = $event->getPosition();
                $this->assertSame($startWith, $first);
            } else {
                $this->assertGreaterThan($startWith, $event->getPosition());
            }
        }

        $first = null;
        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->fromPosition($startWith)->reverse()) as $event) {
            if (!$first) {
                $first = $event->getPosition();
                $this->assertSame($startWith, $first);
            } else {
                $this->assertLessThan($startWith, $event->getPosition());
            }
        }
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryByRevision(EventStore $store)
    {
        $count = $this->getDefaultCount();
        list(,, $aggregates) = $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count, true);

        /** @var \Ramsey\Uuid\UuidInterface $aggregateId */
        $aggregateId = $aggregates[rand(0, count($aggregates) - 1)];

        $first = null;
        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->for($aggregateId)->fromRevision(2)) as $event) {
            if (!$first) {
                $first = $event->getRevision();
                $this->assertSame(2, $first);
            } else {
                $this->assertGreaterThan(2, $event->getRevision());
            }
        }

        $first = null;
        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->for($aggregateId)->fromRevision(2)->reverse()) as $event) {
            if (!$first) {
                $first = $event->getRevision();
                $this->assertSame(2, $first);
            } else {
                $this->assertLessThan(2, $event->getRevision());
            }
        }
    }

    /**
     * @dataProvider getEventStore
     */
    public function testStoreQueryAll(EventStore $store)
    {
        $count = $this->getDefaultCount();
        $this->generateLotsAndLotsOfEvents($store, new \DateTime(), $this->getRandomNames(), $count);

        $previous = null;
        $previousPosition = null;
        $total = 0;
        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getAllEvents() as $event) {
            if ($previous) {
                $this->assertGreaterThan($previous, $event->createdAt());
                $this->assertGreaterThan($previousPosition, $event->getPosition());
            }
            $previousPosition = $event->getPosition();
            $previous = $event->createdAt();
            $total++;
        }
        $this->assertSame($count, $total);

        $previous = null;
        $previousPosition = null;
        $total = 0;
        /** @var \MakinaCorpus\EventSourcing\Event $event */
        foreach ($store->getEventsWith($store->createQuery()->reverse()) as $event) {
            if ($previous) {
                $this->assertLessThan($previous, $event->createdAt());
                $this->assertLessThan($previousPosition, $event->getPosition());
            }
            $previousPosition = $event->getPosition();
            $previous = $event->createdAt();
            $total++;
        }
        $this->assertSame($count, $total);
    }
}
