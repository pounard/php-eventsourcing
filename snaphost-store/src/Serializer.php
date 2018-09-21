<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

interface Serializer
{
    /**
     * Serialize aggregate
     *
     * @param mixed $aggregate
     *   Arbitrary aggregate, might be anything
     *
     * @return mixed
     *   Can be anything, as long as the store can store it, in real life for
     *   most implementation, this should return a string, but for more advanced
     *   storage backend, it could be anything more revelant to the backend.
     */
    public function serialize($aggregate);

    /**
     * Unserialize aggregate
     *
     * @param mixed $value
     *   The anything serialize() returned.
     *
     * @return null|mixed
     *   The original aggregate serialize() got as parameter. If null is
     *   returned, this means that the data is broken, storage backend has the
     *   responsability of invalidating the entry and return null itself.
     */
    public function unserialize($value);
}
