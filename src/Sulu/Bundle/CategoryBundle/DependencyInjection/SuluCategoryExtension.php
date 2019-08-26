<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\DependencyInjection;

use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryNameMissingException;
use Sulu\Bundle\CategoryBundle\Exception\KeywordIsMultipleReferencedException;
use Sulu\Bundle\CategoryBundle\Exception\KeywordNotUniqueException;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluCategoryExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('command.xml');

        $this->configurePersistence($config['objects'], $container);
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            CategoryIdNotFoundException::class => 404,
                            CategoryKeyNotFoundException::class => 404,
                            CategoryKeyNotUniqueException::class => 409,
                            CategoryNameMissingException::class => 400,
                            KeywordIsMultipleReferencedException::class => 409,
                            KeywordNotUniqueException::class => 409,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'directories' => [
                            [
                                'name' => 'sulu_category',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Bundle\CategoryBundle\Entity',
                            ],
                        ],
                    ],
                ]
            );
        }

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
                        'categories' => [
                            'routes' => [
                                'list' => 'sulu_category.get_categories',
                                'detail' => 'sulu_category.get_category',
                            ],
                        ],
                        'category_keywords' => [
                            'routes' => [
                                'list' => 'sulu_category.get_category_keywords',
                                'detail' => 'sulu_category.get_category_keyword',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'category_selection' => [
                                'default_type' => 'list',
                                'resource_key' => 'categories',
                                'types' => [
                                    'list' => [
                                        'adapter' => 'tree_table_slim',
                                        'list_key' => 'categories',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_search',
                [
                    'indexes' => [
                        'category' => [
                            'name' => 'sulu_category.categories',
                            'icon' => 'su-tag',
                            'route' => [
                                'name' => CategoryAdmin::EDIT_FORM_ROUTE,
                                'result_to_route' => [
                                    'id' => 'id',
                                    'locale' => 'locale',
                                ],
                            ],
                            'security_context' => 'sulu.settings.categories',
                        ],
                    ],
                ]
            );
        }
    }
}
