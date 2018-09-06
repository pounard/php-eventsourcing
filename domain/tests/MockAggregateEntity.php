<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain\Tests;

use MakinaCorpus\EventSourcing\Domain\Entity\AggregateEntity;

/**
 * @see validation.yml
 */
final class MockAggregateEntity extends AggregateEntity
{
    private $foo;
    private $bar;
    private $someOtherProperty;

    static public function getAllowedFields(): array
    {
        return [
            'foo', 'bar', 'someOtherProperty'
        ];
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function getSomeOtherProperty()
    {
        return $this->someOtherProperty;
    }
}
