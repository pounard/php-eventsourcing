<?php

namespace MakinaCorpus\EventSourcing\Domain;

use App\Aggregate\MailDefinition;
use App\Repository\MailDefinitionRepository;
use MakinaCorpus\EventSourcing\Domain\Repository\DefaultRepository;
use MakinaCorpus\EventSourcing\EventStore\Event;
use MakinaCorpus\EventSourcing\EventStore\EventStoreFactory;

/**
 * Aggregate repository can only create and load instances, in the event
 * sourcing database model, update and delete operations don't exist.
 *
 * By contrast to more common CRUD based-model, create() operation does
 * not store anything, but creates an empty instance, aggregate will only
 * be created when events will be sent with its aggregate identifier.
 *
 * In this class, the only method that actually hits a database is load().
 */
final class RepositoryFactory
{
    private $eventStoreFactory;
    private $namespaceMap;
    private $repositories;
    private $services;

    /**
     * Default constructor
     */
    public function __construct(EventStoreFactory $eventStoreFactory, array $namespaceMap = [], array $services = [])
    {
        $this->eventStoreFactory = $eventStoreFactory;
        // @todo do not validate in production mode?
        $this->namespaceMap = $this->validateNamespaces($namespaceMap);
        $this->services = $this->validateServices($services);

        // @todo Fix this...
        $this->services = [
            MailDefinition::class => MailDefinitionRepository::class,
        ];
    }

    private function validateNamespaces(array $namespaces): array
    {
        foreach ($namespaces as $className => $namespace) {
            if (!\is_string($namespace)) {
                throw new \InvalidArgumentException(\sprintf("Namespace for %s is not a string", $className));
            }
        }
        return $namespaces;
    }

    private function validateServices(array $services): array
    {
        foreach ($services as $className => $service) {
            if (!$service instanceof Repository) {
                throw new \InvalidArgumentException(\sprintf("Repository service for class %s does not implement %s", $className, Repository::class));
            }
        }
        return $services;
    }

    /**
     * Get single repository
     */
    public function getRepository(string $className): Repository
    {
        // @todo this could be optimized
        $repositoryClassName = DefaultRepository::class;
        if (isset($this->services[$className])) {
            $repositoryClassName = $this->services[$className];
        }

        return $this->repositories[$className] ?? (
            $this->repositories[$className] = new $repositoryClassName(
                $className,
                $this->eventStoreFactory->getEventStore(
                    $this->namespaceMap[$className] ?? Event::NAMESPACE_DEFAULT
                )
            )
        );
    }
}
