<?php

namespace MakinaCorpus\EventSourcing\Domain;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

abstract class Handler implements MessageHandlerInterface
{
    private $repositoryFactory;

    final public function setRepository(RepositoryFactory $repositoryFactory)
    {
        $this->repositoryFactory = $repositoryFactory;
    }

    final public function getRepository(string $className): Repository
    {
        return $this->repositoryFactory->getRepository($className);
    }
}
