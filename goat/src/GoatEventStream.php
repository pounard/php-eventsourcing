<?php

namespace MakinaCorpus\EventSourcing\Goat;

use Goat\Runner\ResultIteratorInterface;
use MakinaCorpus\EventSourcing\Event;
use MakinaCorpus\EventSourcing\EventStream;
use Ramsey\Uuid\Uuid;

final class GoatEventStream implements \IteratorAggregate, EventStream
{
    private $namespace;
    private $result;

    /**
     * Default constructor
     */
    public function __construct(ResultIteratorInterface $result, string $namespace)
    {
        $this->namespace = $namespace;
        $this->result = $result;
    }

    /**
     * Convert goat row to event
     */
    public static function fromRow(array $row, string $namespace): Event
    {
        return Event::fromEventStore(
            $namespace,
            $row['position'],
            // @todo Goat does not natively support UUID yet
            Uuid::fromString($row['aggregate_id']),
            $row['revision'],
            Uuid::fromString($row['root_aggregate_id']),
            $row['created_at'],
            $row['name'],
            @json_decode($row['data'], true) ?? [],
            // Not all RDBMS natively support boolean
            (bool)$row['is_published']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->result as $row) {
            yield self::fromRow($row, $this->namespace);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->result->countRows();
    }
}
