<?php

namespace MakinaCorpus\EventSourcing\Goat\Tests;

use Goat\Driver\Dsn;
use Goat\Driver\ExtPgSQL\ExtPgSQLConnection;
use Goat\Driver\PDO\PDOPgSQLConnection;
use Goat\Runner\RunnerInterface;
use MakinaCorpus\EventSourcing\Goat\GoatEventStoreFactory;
use MakinaCorpus\EventSourcing\Tests\EventStoreTest;
use MakinaCorpus\EventSourcing\EventStore;
use Goat\Driver\PDO\PDOMySQLConnection;

/**
 * Tests the views
 */
final class GoatEventStoreTest extends EventStoreTest
{
    private $tables = [];

    private function createPDOMySQLRunner()
    {
        $uri      = getenv('PDO_MYSQL_DSN');
        $username = getenv('PDO_MYSQL_USERNAME');
        $password = getenv('PDO_MYSQL_PASSWORD');

        if (!$uri || !$username || !$password) {
            return;
        }

        return new PDOMySQLConnection(new Dsn($uri, $username, $password));
    }

    private function createPDOPgSQLRunner()
    {
        $uri      = getenv('PDO_PGSQL_DSN');
        $username = getenv('PDO_PGSQL_USERNAME');
        $password = getenv('PDO_PGSQL_PASSWORD');

        if (!$uri || !$username || !$password) {
            return;
        }

        return new PDOPgSQLConnection(new Dsn($uri, $username, $password));
    }

    private function createExtPgSQLRunner()
    {
        $uri      = getenv('EXT_PGSQL_DSN');
        $username = getenv('EXT_PGSQL_USERNAME');
        $password = getenv('EXT_PGSQL_PASSWORD');

        if (!$uri || !$username || !$password) {
            return;
        }

        return new ExtPgSQLConnection(new Dsn($uri, $username, $password));
    }

    private function createEventStoreFrom(RunnerInterface $runner): EventStore
    {
        $namespace = \uniqid('test_');
        $factory = new GoatEventStoreFactory($runner);
        $store = $factory->getEventStore($namespace);

        return $store;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventStore()
    {
        if ($runner = $this->createExtPgSQLRunner()) {
            yield [$this->createEventStoreFrom($runner)];
        }
        if ($runner = $this->createPDOPgSQLRunner()) {
            yield [$this->createEventStoreFrom($runner)];
        }
        if ($runner = $this->createPDOMySQLRunner()) {
            yield [$this->createEventStoreFrom($runner)];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCount(): int
    {
        return 125;
    }
}
