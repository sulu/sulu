<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\Doctrine\Types\EncryptArray;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 */
class SuluPersistenceExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                    'dbal' => [
                        'types' => [
                            'encrypt_array' => EncryptArray::class,
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_persistence.encryption_key', $config['encryption_key']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }
}
