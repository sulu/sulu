<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Extends the container and initializes the preview budle.
 */
class SuluPreviewExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_preview.mode', $config['mode']);
        $container->setParameter('sulu_preview.delay', $config['delay']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $cacheAdapter = $config['cache_adapter'];

        if ($config['cache']['type']) {
            if (!$container->hasExtension('doctrine_cache')) {
                throw new \RuntimeException(
                    'Deprecated "sulu_preview.cache" configuration used, but DoctrineCacheBundle was not registered.' . PHP_EOL .
                    'Register the DoctrineCacheBundle or use the "sulu_preview.cache_adapter" configuration.'
                );
            }

            $container->prependExtensionConfig('doctrine_cache',
                [
                    'aliases' => [
                        'sulu_preview.preview.cache' => 'sulu_preview',
                    ],
                    'providers' => [
                        'sulu_preview' => $config['cache'],
                    ],
                ]
            );
        } else {
            $container->prependExtensionConfig('framework',
                [
                    'cache' => [
                        'pools' => [
                            'sulu_preview.preview.cache' => [
                                'adapter' => $cacheAdapter,
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
