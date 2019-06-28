<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluSearchExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('jms_serializer', [
            'metadata' => [
                'directories' => [
                    [
                        'name' => 'massive_search',
                        'path' => realpath(__DIR__ . '/..') . '/Resources/config/serializer/massive',
                        'namespace_prefix' => 'Massive\Bundle\SearchBundle\Search',
                    ],
                    [
                        'name' => 'sulu_search',
                        'path' => realpath(__DIR__ . '/..') . '/Resources/config/serializer/sulu',
                        'namespace_prefix' => 'Sulu\Bundle\SearchBundle\Search',
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('massive_search', [
            'services' => [
                'factory' => 'sulu_search.search.factory',
            ],
            'persistence' => [
                'doctrine_orm' => [
                    'enabled' => true,
                ],
            ],
            'adapters' => [
                'zend_lucene' => [
                    'basepath' => '%kernel.project_dir%/var/indexes',
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_search.indexes', $config['indexes']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('search.xml');
        $loader->load('build.xml');
    }
}
