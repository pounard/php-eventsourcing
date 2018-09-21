<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

final class PhpSerializer implements Serializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize($aggregate)
    {
        return \serialize($aggregate);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        if (false === ($aggregate = @\unserialize($value))) {
            return null;
        }

        return $aggregate;
    }
}