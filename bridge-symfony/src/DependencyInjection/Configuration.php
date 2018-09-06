<?php

namespace MakinaCorpus\EventSourcing\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        // $rootNode = $treeBuilder->root('event_sourcing', 'array');

        return $treeBuilder;
    }
}
