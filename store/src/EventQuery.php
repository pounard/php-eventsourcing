<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore;

/**
 * Event query builder
 */
interface EventQuery
{
    /**
     * Set reverse order search
     */
    public function reverse(bool $toggle = false);

    /**
     * Fetch events starting from position
     */
    public function fromPosition(int $position): EventQuery;

    /**
     * Fetch events starting from revision
     */
    public function fromRevision(int $revision): EventQuery;

    /**
     * Fetch events for aggregate
     *
     * @param string|\Ramsey\Uuid\UuidInterface $aggregateId
     */
    public function for($aggregateId): EventQuery;

    /**
     * Fetch with aggregate type
     */
    public function withType(string $aggregateType): EventQuery;

    /**
     * Fetch with the given event names
     *
     * @param string|string[] $nameOrNames
     */
    public function withName($nameOrNames): EventQuery;

    /**
     * Fetch events starting from date, ignored if date bounds are already set using betweenDate()
     */
    public function fromDate(\DateTimeInterface $from): EventQuery;

    /**
     * Fetch event between provided dates, order does not matter, will override fromDate()
     */
    public function betweenDates(\DateTimeInterface $from, \DateTimeInterface $to): EventQuery;

    /**
     * Query within event values.
     *
     * Important warning:
     *   - driver might not suppor this feature, exceptions will be thrown,
     *   - value must be scalar, complex structures will not be queried.
     */
    public function withData(string $name, $value): EventQuery;
}
