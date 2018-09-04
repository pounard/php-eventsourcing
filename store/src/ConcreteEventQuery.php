<?php

namespace MakinaCorpus\EventSourcing\EventStore;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Event query builder concrete implementation
 *
 * EventStores must all support all filters, the only allowed limited support is for
 * the arbitrary event values: if driver does not support it, it must raise exceptions.
 */
final class ConcreteEventQuery implements EventQuery
{
    private $aggregateId;
    private $aggregateTypes = [];
    private $arbitraryDataFilters = [];
    private $backendSupportsArbitraryFilters = false;
    private $dateHigherBound;
    private $dateLowerBound;
    private $names = [];
    private $position = 0;
    private $reverse = false;
    private $revision = 0;

    /**
     * Default constructor
     */
    public function __construct($backendSupportsArbitraryFilters = false)
    {
        $this->backendSupportsArbitraryFilters = $backendSupportsArbitraryFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(bool $toggle = true)
    {
        $this->reverse = $toggle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fromPosition(int $position): EventQuery
    {
        $this->position = $position;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fromRevision(int $revision): EventQuery
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Convert value to UUID, raise exception in case of failure
     */
    private function validateUuid($uuid): UuidInterface
    {
        if (\is_string($uuid)) {
            $uuid = Uuid::fromString($uuid);
        }
        if (!$uuid instanceof UuidInterface) {
            throw new \InvalidArgumentException(\sprintf("Aggregate identifier must be a valid UUID string or instanceof of %s: '%s' given", UuidInterface::class, (string)$uuid));
        }
        return $uuid;
    }

    /**
     * {@inheritdoc}
     */
    public function for($aggregateId): EventQuery
    {
        $this->aggregateId = $this->validateUuid($aggregateId);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withType($typeOrTypes): EventQuery
    {
        \assert(\is_array($typeOrTypes) || \is_string($typeOrTypes));

        $this->aggregateTypes = \array_unique($this->aggregateTypes += \array_values((array)$typeOrTypes));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withName($nameOrNames): EventQuery
    {
        \assert(\is_array($nameOrNames) || \is_string($nameOrNames));

        $this->names = \array_unique($this->names += \array_values((array)$nameOrNames));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDate(\DateTimeInterface $from): EventQuery
    {
        if ($this->dateHigherBound) {
            \trigger_error(\sprintf("Query has already betweenDates() set, fromDate() call is ignored"), E_USER_WARNING);
        } else {
            $this->dateLowerBound = $from;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function betweenDates(\DateTimeInterface $from, \DateTimeInterface $to): EventQuery
    {
        if ($this->dateLowerBound && !$this->dateHigherBound) {
            \trigger_error(\sprintf("Query has already fromDate() set, betweenDates() call overrides it"), E_USER_WARNING);
        }

        if ($from < $to) {
            $this->dateLowerBound = $from;
            $this->dateHigherBound = $to;
        } else {
            $this->dateLowerBound = $to;
            $this->dateHigherBound = $from;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withData(string $name, $value): EventQuery
    {
        if (!$this->backendSupportsArbitraryFilters) {
            throw new \InvalidArgumentException(\sprintf("Event store driver does not support abitrary data query"));
        }
        if (!\is_null($value) && !\is_scalar($value)) {
            throw new \InvalidArgumentException(\sprintf("Query on arbitrary data values must be over scalar or null values"));
        }

        $this->arbitraryDataFilters[$name] = $value;

        return $this;
    }

    /**
     * Is query in reverse order
     */
    public function isReverse(): bool
    {
        return $this->reverse;
    }

    /**
     * Get start position
     */
    public function getStartPosition(): int
    {
        return $this->position;
    }

    /**
     * Get start revision
     */
    public function getStartRevision(): int
    {
        return $this->revision;
    }

    /**
     * Had aggregate filter
     */
    public function hasAggregateId(): bool
    {
        return null !== $this->aggregateId;
    }

    /**
     * Get aggregate filter
     */
    public function getAggregateId(): UuidInterface
    {
        if (!$this->aggregateId) {
            throw new \BadMethodCallException(\sprintf("Query has no aggregate identifier set, please call hasAggregateId() first"));
        }

        return $this->aggregateId;
    }

    /**
     * Get aggregate types filter
     */
    public function getAggregateTypes(): array
    {
        return $this->aggregateTypes;
    }

    /**
     * Get arbitrary data filters
     */
    public function getArbitraryDataFilters(): array
    {
        return $this->arbitraryDataFilters;
    }

    /**
     * Get event names filter
     *
     * @return string[]
     */
    public function getEventNames(): array
    {
        return $this->names;
    }

    /**
     * Get arbitrary data filters
     *
     * @todo
     *   Improve this - hasDateBounds() / getDateBounds() / hasStartDate() / getStartDate()
     *
     * @return null|]|\DateTimeInterface[]
     *   Fitst values is the start date, or null if no filter set, second value
     *   is null if first is null, and can be the higher bound.
     */
    public function getDateBounds(): array
    {
        return [$this->dateLowerBound, $this->dateHigherBound];
    }
}
