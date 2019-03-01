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
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_preview.mode', $config['mode']);
        $container->setParameter('sulu_preview.delay', $config['delay']);
        $container->setParameter('sulu_preview.defaults.analytics_key', $config['defaults']['analytics_key']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
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
                        'sulu_preview.preview.cache' => 'sulu_preview',
                    ],
                    'providers' => [
                        'sulu_preview' => $config['cache'],
                    ],
                ]
            );
        }
    }
}
