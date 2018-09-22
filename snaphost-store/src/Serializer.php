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
     *   Serialized linear representation, this is not type hinted with the
     *   string type here, else it may break with nul chars.
     */
    public function serialize($aggregate): string;

    /**
     * Unserialize aggregate
     *
     * @param string $value
     *   The anything serialize() returned.
     *
     * @return null|mixed
     *   The original aggregate serialize() got as parameter. If null is
     *   returned, this means that the data is broken, storage backend has the
     *   responsability of invalidating the entry and return null itself.
     */
    public function unserialize(string $value);
}
