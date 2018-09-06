<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain\Tests;

use MakinaCorpus\EventSourcing\Domain\Repository;
use MakinaCorpus\EventSourcing\Domain\RepositoryFactory;
use MakinaCorpus\EventSourcing\EventStore\ArrayEventStoreFactory;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private function createRepository(string $aggregateType): Repository
    {
        return (new RepositoryFactory(new ArrayEventStoreFactory()))->getRepository($aggregateType);
    }

    public function testCannotUseNonExistingClasses()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Aggregate class .* does not exist/');
        $this->createRepository('Some_ReallyNon_Existing_Class');
    }

    public function testClassMustExtendAggregate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Aggregate class .* must extend class .*Aggregate/');
        $this->createRepository(TestCase::class);
    }
}
