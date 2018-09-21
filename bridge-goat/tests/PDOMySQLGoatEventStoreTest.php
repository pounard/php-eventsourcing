<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Bridge\Goat\Tests;

use Goat\Driver\Dsn;
use Goat\Driver\PDO\PDOMySQLConnection;
use Goat\Runner\RunnerInterface;

final class PDOMySQLGoatEventStoreTest extends AbstractGoatEventStoreTest
{
    /**
     * {@inheritdoc}
     */
    protected function createRunner(): RunnerInterface
    {
        $uri      = \getenv('PDO_MYSQL_DSN');
        $username = \getenv('PDO_MYSQL_USERNAME');
        $password = \getenv('PDO_MYSQL_PASSWORD');

        if (!$uri) {
            $this->markTestSkipped();
        }

        return new PDOMySQLConnection(new Dsn($uri, $username, $password));
    }
}
