<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\Domain\Entity;

use MakinaCorpus\EventSourcing\Domain\Handler;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @todo
 *   - getAllowedCommands() on abstract aggregate entity class
 *   - default implemetnation for this command, with a closure created by the entity?
 */
class AggregateEntityHandler extends Handler implements MessageSubscriberInterface
{
    private $validator;

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

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
     * Ensure there is no extra fields, call validator if necessary
     */
    private function validateFields(AggregateEntity $aggregate, array $data)
    {
        $updatedPropertyNames = \array_keys($data);

        $allowedFields = $aggregate::getAllowedFields();
        if ($extraFields = \array_diff($updatedPropertyNames, $allowedFields)) {
              throw new \InvalidArgumentException(\sprintf('"%s" are not allowed properties for class %s', \implode('", "', $extraFields), \get_class($aggregate)));
        }

        if ($this->validator) { // Validator component support is optional.
            $messages = [];

            foreach ($updatedPropertyNames as $propertyName) {
                $violations = $this->validator->validateProperty($aggregate, $propertyName);

                if ($violations->count()) {
                    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
                    foreach ($violations as $violation) {
                        $messages[] = \sprintf("%s: %s", $violation->getPropertyPath(), $violation->getMessage());
                    }
                }
            }

            if ($messages) {
                throw new \InvalidArgumentException(\sprintf("Validation error: %s", \implode(', ' , $messages)));
            }
        }
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
            $this->validateFields($aggregate, $data = $command->getUpdatedData());

            $aggregate->updateWith($data);

        } catch (\Throwable $e) {
            $aggregate->updateFailedWith($command->getUpdatedData(), $e->getMessage());

            throw $e;
        }
    }
}
