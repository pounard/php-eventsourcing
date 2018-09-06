<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain\Tests;

use MakinaCorpus\EventSourcing\Domain\Repository;
use MakinaCorpus\EventSourcing\Domain\RepositoryFactory;
use MakinaCorpus\EventSourcing\Domain\Entity\AggregateEntityHandler;
use MakinaCorpus\EventSourcing\EventStore\ArrayEventStoreFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * All other query class methods are implicitely tested along other tests, this test
 * case is only meant to test edge cases.
 */
class AggregateEntityTest extends TestCase
{
    private $repositoryFactory;

    private function getRepositoryFactory(): RepositoryFactory
    {
        return $this->repositoryFactory ?? ($this->repositoryFactory = new RepositoryFactory(new ArrayEventStoreFactory()));
    }

    private function createRepository(string $aggregateType): Repository
    {
        return $this->getRepositoryFactory()->getRepository($aggregateType);
    }

    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->addYamlMapping(__DIR__.'/validation.yml')
            ->getValidator()
        ;
    }

    private function createHandler(): AggregateEntityHandler
    {
        $ret = new AggregateEntityHandler();
        $ret->setRepository($this->getRepositoryFactory());
        $ret->setValidator($this->createValidator());

        return $ret;
    }

    public function testNonAllowedFieldsRaiseErrors()
    {
        $handler = $this->createHandler();
        $repository = $this->createRepository(MockAggregateEntity::class);
        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity $aggregate */
        $aggregate = $repository->create();

        $data = ['foo' => 12, 'non_existing_field' => 'test'];
        $command = MockAggregateEntity::createUpdateCommand($aggregate->getId(), $data);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"non_existing_field" are not allowed properties for class .*/');
        $handler->onAggregateEntityUpdate($command);
    }

    public function testValidation()
    {
        $handler = $this->createHandler();
        $repository = $this->createRepository(MockAggregateEntity::class);
        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity $aggregate */
        $aggregate = $repository->create();

        $data = ['foo' => 8, 'someOtherProperty' => null];
        $command = MockAggregateEntity::createUpdateCommand($aggregate->getId(), $data);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/someOtherProperty:/');
        $handler->onAggregateEntityUpdate($command);
    }

    public function testValidationFailedDoesNotUpdateEntity()
    {
        $handler = $this->createHandler();
        $repository = $this->createRepository(MockAggregateEntity::class);
        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity $aggregate */
        $aggregate = $repository->create();

        $data = ['someOtherProperty' => null];
        $command = MockAggregateEntity::createUpdateCommand($aggregate->getId(), $data);

        try {
            $handler->onAggregateEntityUpdate($command);
        } catch (\InvalidArgumentException $e) {}

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity $modifiedAggregate */
        $modifiedAggregate = $repository->load($aggregate->getId());
        $this->assertNull($modifiedAggregate->getFoo());
        $this->assertNull($modifiedAggregate->getSomeOtherProperty());
    }

    public function testNonSetFieldsAreNotValidated()
    {
        $handler = $this->createHandler();
        $repository = $this->createRepository(MockAggregateEntity::class);
        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity $aggregate */
        $aggregate = $repository->create();

        $data = ['foo' => 14];
        $command = MockAggregateEntity::createUpdateCommand($aggregate->getId(), $data);
        $handler->onAggregateEntityUpdate($command);

        /** @var \MakinaCorpus\EventSourcing\Domain\Tests\MockAggregateEntity $modifiedAggregate */
        $modifiedAggregate = $repository->load($aggregate->getId());
        $this->assertSame(14, $modifiedAggregate->getFoo());
        $this->assertNull($modifiedAggregate->getSomeOtherProperty());
    }

    /*
    public function testEventStreamReplayAcceptsInvalidValues()
    {

    }
     */
}
