<?php

namespace MakinaCorpus\EventSourcing\Domain\Entity;

use MakinaCorpus\EventSourcing\Domain\Aggregate;
use MakinaCorpus\EventSourcing\EventStore\Event;
use Ramsey\Uuid\UuidInterface;

abstract class AggregateEntity extends Aggregate
{
    const EVENT_CREATE = 'EntityCreated';
    const EVENT_UPDATE = 'EntityUpdated';
    const EVENT_UPDATE_FAILED = 'EntityUpdateFailed';

    /**
     * Create an update command for this aggregate type
     */
    final static public function createUpdateCommand(UuidInterface $id, array $data)
    {
        return AggregateEntityUpdateCommand::with(static::getType(), $id, $data);
    }

    /**
     * Get fields allowed for update.
     *
     * Default implementation does reflexion magic, you may or may not override this method.
     */
    static public function getAllowedFields(): array
    {
        // @todo this where some more magic should happen and class introspection to be done
    }

    /**
     * Update internal data with provided values, also serves for creation
     */
    final public function updateWith(array $data)
    {
        if ($this->isNew()) {
            $this->occurs(self::EVENT_CREATE, $data);
        } else {
            $this->occurs(self::EVENT_UPDATE, $data);
        }
    }

    /**
     * Update internal data with provided values
     */
    final public function updateFailedWith(array $data, string $message)
    {
        $this->occurs(self::EVENT_UPDATE_FAILED, ['_error' => $message] + $data);
    }

    /**
     * Entity was created
     */
    final protected function whenEntityCreated(Event $event)
    {
        $this->whenEntityUpdated($event);
    }

    /**
     * Entity was updated
     */
    final protected function whenEntityUpdated(Event $event)
    {
        // @todo use a more generic hydrator?
        $func = \Closure::bind(function () use ($event) {
            // Allow invalid data to be set, past event can reflect an invalid
            // state for present code, it nevertheless must never fail when at
            // load.
            foreach ($event->getData() as $name => $value) {
                $this->{$name} = $value;
            }
        }, $this, \get_called_class());

        $func();
    }

    /**
     * Per default we log update failures, this allows to keep it in history
     * but in real life, we don't want to store that in the object state, so
     * this just does nothing.
     */
    final protected function whenEntityUpdateFailed(Event $event)
    {
    }
}
