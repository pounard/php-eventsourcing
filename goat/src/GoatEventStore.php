<?php

namespace MakinaCorpus\EventSourcing\Goat;

use Goat\Query\Query;
use Goat\Runner\RunnerInterface;
use Goat\Runner\Transaction;
use MakinaCorpus\EventSourcing\ConcretEventQuery;
use MakinaCorpus\EventSourcing\Event;
use MakinaCorpus\EventSourcing\EventQuery;
use MakinaCorpus\EventSourcing\EventStore;
use MakinaCorpus\EventSourcing\EventStream;
use Ramsey\Uuid\UuidInterface;

final class GoatEventStore implements EventStore
{
    private $namespace = Event::NAMESPACE_DEFAULT;
    private $runner;
    private $tableName;

    /**
     * Default constructor
     *
     * @codeCoverageIgnore
     *   Code coverage does not take into account data provider run methods.
     */
    public function __construct(string $namespace, RunnerInterface $runner, string $tableName)
    {
        $this->namespace = $namespace;
        $this->runner = $runner;
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     *
     * @todo Should we write a variant for RDBMS that don't support RETURNING ?
     */
    public function store(Event $event): Event
    {
        if (false === ($data = \json_encode($event->getData()))) {
            throw new \InvalidArgumentException(\sprintf("Invalid data in event, did you set any non scalar values?"));
        }
        $transaction = null;
        $aggregateId = $event->getAggregateId();

        try {
            // REPEATABLE_READ transaction level allows for fantom read, for our
            // business the only risk is that 2 concurrent process will try to
            // create a new revision for the same aggregate at the exact same
            // time: this would lead to one of the transactions failing.
            // It's probabilistically a very low risk, and it cannot possibly
            // lead to incoherent data in database: worst case scenario is that
            // the user will experience a single error and will be able to retry.
            $transaction = $this->runner->startTransaction(Transaction::REPEATABLE_READ)->start();

            $nextRevisionId = ((int)$this
                ->runner
                ->select($this->tableName)
                ->columnExpression('max(revision)')
                ->condition('aggregate_id', $aggregateId)
                ->execute()
                ->fetchField()
            ) + 1;

            $insert = $this
                ->runner
                ->insertValues($this->tableName)
                ->values([
                    'aggregate_id' => $aggregateId,
                    'revision' => $nextRevisionId,
                    'created_at' => $event->createdAt(),
                    'root_aggregate_id' => $event->getRootAggregateId(),
                    'name' => $event->getName(),
                    'data' => $data,
                    'is_published' => $event->isPublished(),
                ])
            ;

            $row = null;
            if ($this->runner->supportsReturning()) {
                $row = $insert->returning('*')->execute()->fetch();
            } else { // Extra query when backend does not support returning
                $insert->execute();
                $row = $this
                    ->runner
                    ->select($this->tableName)
                    ->condition('aggregate_id', (string)$aggregateId)
                    ->condition('revision', $nextRevisionId)
                    ->execute()
                    ->fetch()
                ;
            }

            $newEvent = GoatEventStream::fromRow($row, $this->namespace);
            $transaction->commit();

            return $newEvent;

        } catch (\Throwable $e) {
            if ($transaction && $transaction->isStarted()) {
                try {
                    $transaction->rollback();
                } catch (\Throwable $rollbackError) {
                    // Do nothing, you are basically fucked.
                }
            }

            throw $e;
        }
    }

    /**
     * Create proper goat query and feed event stream with
     */
    private function queryEvents(ConcretEventQuery $query): GoatEventStream
    {
        $select = $this->runner->select($this->tableName);
        $where = $select->getWhere();
        $dates = $query->getDateBounds();

        if ($names = $query->getEventNames()) {
            $where->isIn('name', $names);
        }
        if ($query->hasAggregateId()) {
            $where->isEqual('aggregate_id', (string)$query->getAggregateId());
        }
        if ($query->hasRootAggregateId()) {
            $where->isEqual('root_aggregate_id', (string)$query->getRootAggregateId());
        }
        if ($dates[0] && $dates[1]) {
            $where->isBetween('created_at', $dates[0], $dates[1]);
        }

        if ($query->isReverse()) {
            // Primary serial with a huge amount of values could recycle
            // deleted values, we cannot fully rely upon it for ordering.
            $select->orderBy('created_at', Query::ORDER_DESC);
            $select->orderBy('position', Query::ORDER_DESC);

            if ($position = $query->getStartPosition()) {
                $where->isLessOrEqual('position', $position);
            }
            if ($revision = $query->getStartRevision()) {
                $where->isLessOrEqual('revision', $revision);
            }

            if ($dates[0] && !$dates[1]) {
                $where->isLess('created_at', $dates[0]);
            }
        } else {
            // Cf. upper note.
            $select->orderBy('created_at', Query::ORDER_ASC);
            $select->orderBy('position', Query::ORDER_ASC);

            if ($position = $query->getStartPosition()) {
                $where->isGreaterOrEqual('position', $position);
            }
            if ($revision = $query->getStartRevision()) {
                $where->isGreaterOrEqual('revision', $revision);
            }

            if ($dates[0] && !$dates[1]) {
                $where->isGreater('created_at', $dates[0]);
            }
        }

        return new GoatEventStream($select->execute(), $this->namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery(): EventQuery
    {
        return new ConcretEventQuery(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllEvents(array $names = []): EventStream
    {
        return $this->queryEvents($this->createQuery()->withName($names));
    }

    /**
     * {@inheritdoc}
     */
    public function getEventsFor(UuidInterface $aggregateId, array $names = []): EventStream
    {
        return $this->queryEvents($this->createQuery()->for($aggregateId)->withName($names));
    }

    /**
     * {@inheritdoc}
     */
    public function getEventsWith(ConcretEventQuery $query): EventStream
    {
        return $this->queryEvents($query);
    }
}
