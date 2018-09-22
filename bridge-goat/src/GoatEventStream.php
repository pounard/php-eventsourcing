<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Bridge\Goat;

use Goat\Runner\ResultIteratorInterface;
use MakinaCorpus\EventSourcing\EventStore\Event;
use MakinaCorpus\EventSourcing\EventStore\EventStream;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class GoatEventStream implements \IteratorAggregate, EventStream
{
    private $result;

    /**
     * Default constructor
     */
    public function __construct(ResultIteratorInterface $result)
    {
        $this->result = $result;
    }

    /**
     * Convert goat row to event
     */
    public static function fromRow(array $row): Event
    {
        return Event::fromEventStore(
            $row['position'],
            $row['aggregate_id'] instanceof UuidInterface ? $row['aggregate_id'] : Uuid::fromString((string)$row['aggregate_id']),
            $row['revision'],
            $row['aggregate_type'],
            $row['created_at'],
            $row['name'],
            // Depending upon the backend driver, JSON type might not be supported
            // (for example with MySQL) case in which we need to convert it from here.
            $row['data'] ? (\is_string($row['data']) ? \json_decode($row['data'], true) : $row['data']) : []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->result as $row) {
            yield self::fromRow($row);
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
