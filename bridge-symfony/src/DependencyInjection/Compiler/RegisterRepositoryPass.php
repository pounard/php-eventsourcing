<?php

namespace MakinaCorpus\EventSourcing\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\EventSourcing\Domain\Repository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class RegisterRepositoryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private $repositoryFactoryId;
    private $repositoryTag;

    /**
     * Default constructor
     */
    public function __construct(
        string $repositoryTag = 'eventsourcing.repository',
        string $repositoryFactoryId = 'eventsourcing.repository_factory'
    ) {
        $this->repositoryFactoryId = $repositoryFactoryId;
        $this->repositoryTag = $repositoryTag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$this->repositoryTag) {
            return;
        }
        if (!$container->hasDefinition($this->repositoryFactoryId)) {
            return;
        }

        $factoryDefinition = $container->getDefinition($this->repositoryFactoryId);

        $services = [];
        $typeToClassMap = [];

        foreach ($this->findAndSortTaggedServices($this->repositoryTag, $container) as $reference) {
            $id = (string)$reference;
            $repositoryDefinition = $container->getDefinition($id);
            $class = $repositoryDefinition->getClass();

            if (!$reflection = $container->getReflectionClass($class)) {
                throw new InvalidArgumentException(sprintf('Class %s used for service "%s" cannot be found.', $class, $id));
            }
            if (!$reflection->implementsInterface(Repository::class)) {
                throw new InvalidArgumentException(sprintf('Service "%s" must implement interface %s.', $id, Repository::class));
            }
            // I would like something better than this, but for now, the repository
            // factory will make direct calls to the container for fetching services.
            $repositoryDefinition->setPublic(true);

            $aggregateClassName = $class::getAggregateClassName();
            $services[$aggregateClassName] = $id;
            $typeToClassMap[$class::getAggregateType()] = $aggregateClassName;
        }

        $arguments = $factoryDefinition->getArguments();
        $arguments[0] = $arguments[0] ?? null;
        $arguments[1] = $typeToClassMap + ($arguments[1] ?? []);
        $arguments[2] = $services;
        $arguments[3] = $arguments[3] ?? [];

        $factoryDefinition->setArguments($arguments);
        $factoryDefinition->addMethodCall('setContainer', [new Reference('service_container')]);
    }
}
