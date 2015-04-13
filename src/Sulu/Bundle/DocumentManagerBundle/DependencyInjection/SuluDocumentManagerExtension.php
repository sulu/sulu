<?php

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class SuluDocumentManagerExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig('jms_serializer', array(
                'metadata' => array(
                    'directories' => array(
                        array(
                            'path' => __DIR__ . '/../Resources/config/serializer',
                            'namespace_prefix' => 'Sulu\Component\DocumentManager',
                        ),
                    ),
                ),
            ));
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->configureDocumentManager($config, $container);
        $loader->load('core.xml');
        $loader->load('behaviors.xml');
        $loader->load('serializer.xml');
    }

    private function configureDocumentManager($config, ContainerBuilder $container)
    {
        $debug = $container->getParameter('kernel.debug');

        $dispatcherId = $debug ? 'sulu_document_manager.event_dispatcher.debug' : 'sulu_document_manager.event_dispatcher.standard';
        $container->setAlias('sulu_document_manager.event_dispatcher', $dispatcherId);

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
