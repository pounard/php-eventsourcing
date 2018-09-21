<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Bridge\Goat\Tests;

use Goat\Driver\Dsn;
use Goat\Driver\ExtPgSQL\ExtPgSQLConnection;
use Goat\Runner\RunnerInterface;

final class ExtPgSQLGoatEventStoreTest extends AbstractGoatEventStoreTest
{
    /**
     * {@inheritdoc}
     */
    protected function createRunner(): RunnerInterface
    {
        $uri      = \getenv('EXT_PGSQL_DSN');
        $username = \getenv('EXT_PGSQL_USERNAME');
        $password = \getenv('EXT_PGSQL_PASSWORD');

        if (!$uri) {
            $this->markTestSkipped();
        }

        return new ExtPgSQLConnection(new Dsn($uri, $username, $password));
    }
}
