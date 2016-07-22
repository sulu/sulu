<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CollaborationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluCollaborationExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('doctrine_cache')) {
            $container->prependExtensionConfig(
                'doctrine_cache',
                [
                    'providers' => [
                        'sulu_collaboration_entity' => [
                            'file_system' => ['directory' => '%sulu.cache_dir%/collaboration/entity'],
                        ],
                        'sulu_collaboration_connection' => [
                            'file_system' => ['directory' => '%sulu.cache_dir%/collaboration/connection'],
                        ],
                    ],
                ]
            );

            $container->prependExtensionConfig(
                'sulu_collaboration',
                [
                    'entity_cache' => 'doctrine_cache.providers.sulu_collaboration_entity',
                    'connection_cache' => 'doctrine_cache.providers.sulu_collaboration_connection',
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->processCollaboration($container, $config);

        $loader->load('services.xml');
    }

    private function processCollaboration(ContainerBuilder $container, $config)
    {
        $container->setParameter('sulu_collaboration.interval', $config['interval']);
        $container->setParameter('sulu_collaboration.threshold', $config['threshold']);

        if (!isset($config['entity_cache'])) {
            throw new InvalidArgumentException('The entity cache service for the collaboration must be configured');
        }
        $container->setAlias('sulu_collaboration.entity_cache', $config['entity_cache']);

        if (!isset($config['connection_cache'])) {
            throw new InvalidArgumentException(
                'The connection cache service for the collaboration must be configured'
            );
        }
        $container->setAlias('sulu_collaboration.connection_cache', $config['connection_cache']);
    }
}
