<?php

namespace MakinaCorpus\EventSourcing\Domain\Entity;

use MakinaCorpus\EventSourcing\Domain\Handler;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

/**
 * @todo
 *   - getAllowedCommands() on abstract aggregate entity class
 *   - default implemetnation for this command, with a closure created by the entity?
 */
class AggregateEntityHandler extends Handler implements MessageSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getHandledMessages(): iterable
    {
        // @todo
        //    - dynamically build this with business methods
        //    - maybe a compilation pass would be better ?
        return [
            AggregateEntityUpdateCommand::class => 'onAggregateEntityUpdate',
        ];
    }

    /**
     * Default handler for aggregate entity update.
     */
    public function onAggregateEntityUpdate(AggregateEntityUpdateCommand $command)
    {
        $repository = $this->getRepository($command->getAggregateType());
        $aggregate = $repository->load($command->getAggregateId());

        if (!$aggregate instanceof AggregateEntity) {
            throw new \InvalidArgumentException(\sprintf(
                "Class %s for entity %s of type %s does not extend %s",
                \get_class($aggregate), $command->getAggregateId(), $command->getAggregateType(), AggregateEntity::class
            ));
        }

        try {
            // @todo check for allowed fields
            // @todo use symfony validator to validate data
            // @todo always let exceptions pass

            $aggregate->updateWith($command->getUpdatedData());

        } catch (\Throwable $e) {
            $aggregate->updateFailedWith($command->getUpdatedData(), $e->getMessage());

            throw $e;
        }
    }
}
