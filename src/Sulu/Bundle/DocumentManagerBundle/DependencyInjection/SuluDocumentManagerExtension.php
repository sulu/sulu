<?php

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class SuluDocumentManagerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->configureDocumentManager($config, $container);
        $loader->load('services.xml');
    }

    private function configureDocumentManager($config, ContainerBuilder $container)
    {
        $realMapping = array();
        foreach ($config['mapping'] as $alias => $mapping) {
            $realMapping[] = array_merge(array(
                'alias' => $alias,
            ), $mapping);
        }
        $container->setParameter('sulu_document_manager.mapping', $realMapping);
        $container->setParameter('sulu_document_manager.namespace_mapping', $config['namespace']);
    }
}
