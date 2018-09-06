<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain;

use MakinaCorpus\EventSourcing\Domain\Repository\DefaultRepository;
use MakinaCorpus\EventSourcing\EventStore\Event;
use MakinaCorpus\EventSourcing\EventStore\EventStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class RepositoryFactory
{
    private $container;
    private $eventStoreFactory;
    private $namespaceMap;
    private $repositories;
    private $services;
    private $typeToClassMap;

    /**
     * Default constructor
     */
    public function __construct(EventStoreFactory $eventStoreFactory, array $typeToClassMap = [], array $services = [], array $namespaceMap = [])
    {
        $this->eventStoreFactory = $eventStoreFactory;
        $this->namespaceMap = $namespaceMap;
        $this->services = $services;
        $this->typeToClassMap = $typeToClassMap;
    }

    /**
     * Set container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get target class name for given aggregate type
     */
    private function getClassNameForType(string $aggregateType): string
    {
        return $this->typeToClassMap[$aggregateType] ?? $aggregateType;
    }

    /**
     * Get namespace for aggregate type
     */
    private function getNamespaceForType(string $aggregateType, string $className): string
    {
        return $this->namespaceMap[$className] ?? $this->namespaceMap[$aggregateType] ?? Event::NAMESPACE_DEFAULT;
    }

    /**
     * Create default repository from given class name or aggregate type
     */
    private function createDefaultRepository(string $aggregateType, string $className): Repository
    {
        if (!\class_exists($className)) {
            throw new \InvalidArgumentException(\sprintf("Aggregate class %s does not exist for aggregate type '%s'", $className, $aggregateType));
        }
        if (!\is_subclass_of($className, Aggregate::class)) {
            throw new \InvalidArgumentException(\sprintf("Aggregate class %s must extend class %s", $className, Aggregate::class));
        }

        $ret = new DefaultRepository();
        $ret->setClassName($className);
        $ret->setEventStore($this->eventStoreFactory->getEventStore($this->getNamespaceForType($aggregateType, $className)));

        return $ret;
    }

    /**
     * Create repository from an existing container service
     */
    private function createRepository(string $aggregateType, string $className): Repository
    {
        if ($this->container) {
            if ($serviceId = $this->services[$className] ?? null) {
                $ret = $this->container->get($serviceId);
                $ret->setClassName($className);
                $ret->setEventStore($this->eventStoreFactory->getEventStore($this->getNamespaceForType($aggregateType, $className)));

                return $ret;
            }
            if ($serviceId = $this->services[$aggregateType] ?? null) {
                $ret = $this->container->get($serviceId);
                $ret->setClassName($className);
                $ret->setEventStore($this->eventStoreFactory->getEventStore($this->getNamespaceForType($aggregateType, $className)));

                return $ret;
            }
        }

        return $this->createDefaultRepository($aggregateType, $className);
    }

    /**
     * Get single repository
     */
    public function getRepository(string $aggregateType): Repository
    {
        $className = $this->getClassNameForType($aggregateType);

        if (isset($this->repositories[$className])) {
            return $this->repositories[$className];
        }

        return $this->repositories[$className] = $this->createRepository($aggregateType, $className);
    }
}
