<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\DependencyInjection;

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

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluCategoryExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configurePersistence($config['objects'], $container);
    }

    /**
     * {@inheritdoc}
     */
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
                    'resources' => [
                        'categories' => [
                            'form' => ['@SuluCategoryBundle/Resources/config/forms/Category.xml'],
                            'datagrid' => '%sulu.model.category.class%',
                            'endpoint' => 'get_categories',
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'category_list' => [
                                'default_type' => 'datagrid',
                                'resource_key' => 'categories',
                                'types' => [
                                    'datagrid' => [
                                        'adapter' => 'tree_table_slim',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
