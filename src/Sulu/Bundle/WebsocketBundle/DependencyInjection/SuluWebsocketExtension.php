<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * {@inheritdoc}
 */
class SuluWebsocketExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_websocket.enabled', $config['enabled']);
        $container->setParameter('sulu_websocket.server.ip_address', $config['server']['ip_address']);
        $container->setParameter('sulu_websocket.server.port', $config['server']['port']);
        $container->setParameter('sulu_websocket.server.http_host', $config['server']['http_host']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('doctrine_cache')) {
            $configs = $container->getExtensionConfig($this->getAlias());
            $config = $this->processConfiguration(new Configuration(), $configs);

            $container->prependExtensionConfig('doctrine_cache',
                [
                    'aliases' => [
                        'sulu_websocket.websocket.cache' => 'sulu_websocket',
                    ],
                    'providers' => [
                        'sulu_websocket' => $config['cache'],
                    ],
                ]
            );
        }
    }
}
