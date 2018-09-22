<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\SnapshotStore;

trait SnapshotStoreTrait
{
    private $serializer;

    /**
     * Set serializer
     */
    final public function setSerializer(Serializer $serializer): void
    {
        $this->serializer = $serializer;
    }

    /**
     * Get serializer
     */
    final public function getSerializer(): Serializer
    {
        return $this->serializer ?? ($this->serializer = new PhpSerializer());
    }
}
