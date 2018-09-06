<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Goat\Tests;

use Goat\Driver\Dsn;
use Goat\Driver\ExtPgSQL\ExtPgSQLConnection;
use Goat\Runner\RunnerInterface;

final class PDOPgSQLGoatEventStoreTest extends AbstractGoatEventStoreTest
{
    /**
     * {@inheritdoc}
     */
    protected function createRunner(): RunnerInterface
    {
        $uri      = \getenv('PDO_PGSQL_DSN');
        $username = \getenv('PDO_PGSQL_USERNAME');
        $password = \getenv('PDO_PGSQL_PASSWORD');

        if (!$uri) {
            $this->markTestSkipped();
        }

        return new ExtPgSQLConnection(new Dsn($uri, $username, $password));
    }
}
