<?php

declare(strict_types=1);

namespace MakinaCorpus\EventSourcing\EventStore;

/**
 * Iterator of Event objects
 */
interface EventStream extends \Traversable, \Countable
{
}
