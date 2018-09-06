<?php

namespace MakinaCorpus\EventSourcing\Bridge\Symfony\DependencyInjection;

use Goat\Bundle\GoatBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EventSourcingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // $configuration = new Configuration($container->getParameter('kernel.debug'));
        // $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if ($container->hasParameter('kernel.bundles') && \in_array(GoatBundle::class, $container->getParameter('kernel.bundles'))) {
            $loader->load('goat.yaml');
        }

        $loader->load('services.yaml');
    }
}
