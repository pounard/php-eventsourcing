<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain\Entity;

use MakinaCorpus\EventSourcing\Domain\Command;
use Ramsey\Uuid\UuidInterface;

/**
 * @todo
 *   - need serializer (for later)
 *   - need single controller, multiple route (rest api, one url per resource type)
 *   - should it need to have allowed data from here?
 *   - (need aggregate <-> string type) registry
 */
final class AggregateEntityUpdateCommand implements Command
{
    private $aggregateId;
    private $aggregateType;
    private $data;
    private $allowedProperties = [];

    /**
     * Disabled contructor, use static factory methods.
     */
    private function __construct()
    {
    }

    /**
     * Create command instance
     */
    public static function with(string $type, UuidInterface $id, array $data): self
    {
        if (empty($data)) {
            throw new \InvalidArgumentException(\sprintf("You cannot update entity %s of type %s without any data to change", $id, $type));
        }

        $ret = new self;
        $ret->aggregateId = $id;
        $ret->aggregateType = $type;
        $ret->data = $data;

        return $ret;
    }

    /**
     * Get aggregate identifier
     */
    public function getAggregateId(): UuidInterface
    {
        return $this->aggregateId;
    }

    /**
     * Get aggregate type
     */
    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    /**
     * Get updated data
     */
    public function getUpdatedData(): array
    {
        return $this->data;
    }
}
