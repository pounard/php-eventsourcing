<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Bridge\Goat;

use Goat\Runner\RunnerInterface;
use MakinaCorpus\EventSourcing\EventStore\Event;
use MakinaCorpus\EventSourcing\SnapshotStore\SnapshotStore;
use MakinaCorpus\EventSourcing\SnapshotStore\SnapshotStoreFactory;

/**
 * @todo Extract table naming into a strategy object?
 */
final class GoatSnapshotStoreFactory implements SnapshotStoreFactory
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
        return "{$namespace}_snapshots";
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
    "aggregate_id" uuid NOT NULL,
    "aggregate_type" varchar(128) NOT NULL DEFAULT 'none',
    "revision" integer NOT NULL,
    "created_at" timestamp NOT NULL,
    "updated_at" timestamp NOT NULL,
    "data" bytea NOT NULL,
    PRIMARY KEY("aggregate_id")
);
QUERY
            );
        } else {
            // MySQL and others with only basics from 20 years ago.
            $this->runner->execute(<<<QUERY
CREATE TABLE IF NOT EXISTS $tableName (
    aggregate_id VARCHAR(36) NOT NULL,
    aggregate_type VARCHAR(128) NOT NULL,
    revision INTEGER UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    data blob NOT NULL,
    PRIMARY KEY(aggregate_id)
);
QUERY
            );
        }
    }

    /**
     * Create namespace
     */
    private function createSnapshotStore(string $namespace = Event::NAMESPACE_DEFAULT): GoatSnapshotStore
    {
        $tableName = $this->getTableName($namespace);

        if (!isset($this->namespaceMap[$namespace])) {
            // throw new \InvalidArgumentException(\sprintf("Namespace '%s' has not configured table in namespace map", $namespace));
            $this->createTableIfNotExists($tableName);
        }

        return new GoatSnapshotStore($this->runner, $tableName);
    }

    /**
     * Get event store for given namespace
     */
    public function getSnapshotStore(string $namespace = Event::NAMESPACE_DEFAULT): SnapshotStore
    {
        return $this->eventStores[$namespace] ?? (
            $this->eventStores[$namespace] = $this->createSnapshotStore($namespace)
        );
    }
}
