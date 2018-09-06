<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain;

use Ramsey\Uuid\UuidInterface;

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
interface Repository
{
    /**
     * Get aggregate type
     */
    static public function getAggregateClassName(): string;

    /**
     * Get aggregate class name
     */
    static public function getAggregateType(): string;

    /**
     * Create new object instance with and initialize its unique identifier.
     *
     * This method does not store the object, you must run events with its
     * identifier to persist it.
     */
    public function create(): Aggregate;

    /**
     * Load an object from its identifier and restore its state using events.
     */
    public function load(UuidInterface $id): Aggregate;
}
