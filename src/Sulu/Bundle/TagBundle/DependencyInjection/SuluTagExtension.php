<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages tag-bundle configuration.
 */
class SuluTagExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'tags' => [
                            'endpoint' => 'get_tags',
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'tag_selection' => [
                                'default_type' => 'auto_complete',
                                'resource_key' => 'tags',
                                'types' => [
                                    'auto_complete' => [
                                        'allow_add' => true,
                                        'display_property' => 'name',
                                        'id_property' => 'name',
                                        'filter_parameter' => 'names',
                                        'search_properties' => ['name'],
                                    ],
                                ],
                            ],
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

        $this->configurePersistence($config['objects'], $container);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }
}
