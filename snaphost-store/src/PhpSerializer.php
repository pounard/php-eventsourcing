<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

final class PhpSerializer implements Serializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize($aggregate): string
    {
        // When serializing classes, some properties names may contain nul
        // chars '\0', in this very specific case, string cast will cause
        // string to be broken in the other side therefore linear object
        // representation to be broken.
        return \base64_encode(\serialize($aggregate));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        if (false === ($aggregate = \unserialize(\base64_decode($value)))) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        return $aggregate;
    }
}