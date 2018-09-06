<?php

namespace MakinaCorpus\EventSourcing\Bridge\Symfony;

use MakinaCorpus\EventSourcing\Bridge\Symfony\DependencyInjection\Compiler\RegisterRepositoryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EventSourcingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterRepositoryPass());
    }
}
