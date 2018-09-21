<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Bridge\Goat;

use Goat\Runner\RunnerInterface;
use MakinaCorpus\EventSourcing\EventStore\Event;
use MakinaCorpus\EventSourcing\EventStore\EventStore;
use MakinaCorpus\EventSourcing\EventStore\EventStoreFactory;

final class GoatEventStoreFactory implements EventStoreFactory
{
    private $eventStores = [];
    private $namespaceMap = [];
    private $runner;

    /**
     * Default constructor
     */
    public function __construct(RunnerInterface $runner, array $namespaceMap = [])
    {
        $this->namespaceMap = $namespaceMap;
        $this->runner = $runner;
    }

    /**
     * Get database runner, made public for testing
     */
    public function getRunner(): RunnerInterface
    {
        return $this->runner;
    }

    /**
     * Get database table for namespace
     */
    public function getTableName(string $namespace): string
    {
        return "{$namespace}_events";
    }

    /**
     * @todo Proper namespace escaping, injection IS possible.
     */
    private function createTableIfNotExists(string $tableName)
    {
        if (\preg_match('/pg/i', $this->runner->getDriverName())) {
            // PgSQL is the only modern RDBMS actually supported.
            $this->runner->execute(<<<QUERY
CREATE TABLE IF NOT EXISTS "$tableName" (
    "position" bigserial NOT NULL,
    "aggregate_id" uuid NOT NULL,
    "aggregate_type" varchar(128) NOT NULL DEFAULT 'none',
    "revision" integer NOT NULL,
    "created_at" timestamp NOT NULL DEFAULT NOW(),
    "name" varchar(128) NOT NULL,
    "data" jsonb NOT NULL,
    PRIMARY KEY("position"),
    UNIQUE ("aggregate_id", "revision")
);
QUERY
            );
        } else {
            // MySQL and others with only basics from 20 years ago.
            $this->runner->execute(<<<QUERY
CREATE TABLE IF NOT EXISTS $tableName (
    position INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    aggregate_id VARCHAR(36) NOT NULL,
    aggregate_type VARCHAR(128) NOT NULL DEFAULT 'none',
    revision INTEGER UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT NOW(),
    name VARCHAR(128) NOT NULL,
    data text NOT NULL,
    PRIMARY KEY(position),
    UNIQUE (aggregate_id, revision)
);
QUERY
            );
        }
    }

    /**
     * Create namespace
     */
    private function createEventStore(string $namespace = Event::NAMESPACE_DEFAULT): GoatEventStore
    {
        $tableName = $this->getTableName($namespace);

        if (!isset($this->namespaceMap[$namespace])) {
            // throw new \InvalidArgumentException(\sprintf("Namespace '%s' has not configured table in namespace map", $namespace));
            $this->createTableIfNotExists($tableName);
        }

        return new GoatEventStore($namespace, $this->runner, $tableName);
    }

    /**
     * Get event store for given namespace
     */
    public function getEventStore(string $namespace = Event::NAMESPACE_DEFAULT): EventStore
    {
        return $this->eventStores[$namespace] ?? (
            $this->eventStores[$namespace] = $this->createEventStore($namespace)
        );
    }
}
