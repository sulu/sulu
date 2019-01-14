<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\DependencyInjection;

use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Bundle\PageBundle\Form\Type\HomeDocumentType;
use Sulu\Bundle\PageBundle\Form\Type\PageDocumentType;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluPageExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'internal_links' => [
                                'default_type' => 'datagrid_overlay',
                                'resource_key' => 'pages',
                                'types' => [
                                    'datagrid_overlay' => [
                                        'adapter' => 'column_list',
                                        'display_properties' => ['title', 'url'],
                                        'icon' => 'su-document',
                                        'label' => 'sulu_page.selection_label',
                                        'overlay_title' => 'sulu_page.selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_internal_link' => [
                                'default_type' => 'datagrid_overlay',
                                'resource_key' => 'pages',
                                'types' => [
                                    'datagrid_overlay' => [
                                        'adapter' => 'column_list',
                                        'display_properties' => ['title'],
                                        'empty_text' => 'sulu_page.no_page_selected',
                                        'icon' => 'su-document',
                                        'overlay_title' => 'sulu_page.single_selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'resources' => [
                        'pages_seo' => [
                            'endpoint' => 'get_page-seos',
                        ],
                        'pages_excerpt' => [
                            'endpoint' => 'get_page-excerpts',
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig('framework', [
                'form' => true,
            ]);
        }

        if ($container->hasExtension('sulu_core')) {
            $container->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'paths' => [
                                'page_extension' => [
                                    'path' => __DIR__ . '/../Content/templates',
                                    'type' => 'page',
                                ],
                                'home' => [
                                    'path' => '%kernel.project_dir%/config/templates/pages',
                                    'type' => 'home',
                                ],
                                'page' => [
                                    'path' => '%kernel.project_dir%/config/templates/pages',
                                    'type' => 'page',
                                ],
                            ],
                            'type_map' => [
                                'page' => PageBridge::class,
                                'home' => PageBridge::class,
                            ],
                            'resources' => [
                                'pages' => [
                                    'datagrid' => BasePageDocument::class,
                                    'endpoint' => 'get_pages',
                                ],
                            ],
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
                                'name' => 'sulu_page',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Bundle\PageBundle',
                            ],
                            [
                                'name' => 'sulu_page.component.content',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\Content',
                            ],
                            [
                                'name' => 'sulu_page.component.webspace',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\Webspace',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            ResourceLocatorAlreadyExistsException::class => 409,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_page',
                [
                    'search' => [
                        'mapping' => [
                            PageDocument::class => ['index' => 'page', 'decorate_index' => true],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_document_manager')) {
            $container->prependExtensionConfig(
                'sulu_document_manager',
                [
                    'mapping' => [
                        'page' => ['class' => PageDocument::class, 'phpcr_type' => 'sulu:page', 'form_type' => PageDocumentType::class],
                        'home' => ['class' => HomeDocument::class, 'phpcr_type' => 'sulu:home', 'form_type' => HomeDocumentType::class],
                        'route' => ['class' => RouteDocument::class, 'phpcr_type' => 'sulu:path'],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'resources' => [
                        'webspaces' => [
                            'endpoint' => 'get_webspaces',
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        if (array_key_exists('SuluSearchBundle', $bundles)) {
            $this->processSearch($config, $loader, $container);
        }

        $loader->load('services.xml');
        $loader->load('smart_content.xml');
        $loader->load('teaser.xml');
        $loader->load('content_types.xml');
        $loader->load('structure.xml');
        $loader->load('extension.xml');
        $loader->load('form.xml');
        $loader->load('compat.xml');
        $loader->load('document.xml');
        $loader->load('serializer.xml');
        $loader->load('export.xml');
        $loader->load('import.xml');
        $loader->load('command.xml');
        $loader->load('link-tag.xml');

        if (array_key_exists('SuluAudienceTargetingBundle', $bundles)) {
            $loader->load('rule.xml');
        }

        $this->appendDefaultAuthor($config, $container);

        $container->setParameter('sulu_page.seo', $config['seo']);

        // the service "controller_name_converter" is private use an alias to make it public
        $container->setAlias('sulu_page.controller_name_converter', 'controller_name_converter')->setPublic(true);
    }

    private function processSearch($config, LoaderInterface $loader, ContainerBuilder $container)
    {
        $container->setParameter('sulu_page.search.mapping', $config['search']['mapping']);
        $loader->load('search.xml');
    }

    /**
     * Append configuration for article "set_default_author".
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function appendDefaultAuthor(array $config, ContainerBuilder $container)
    {
        $container->setParameter('sulu_page.default_author', $config['default_author']);
        if (!$container->hasParameter('sulu_document_manager.mapping')) {
            $container->prependExtensionConfig(
                'sulu_document_manager',
                [
                    'mapping' => [
                        'page' => ['set_default_author' => $config['default_author']],
                        'home' => ['set_default_author' => $config['default_author']],
                    ],
                ]
            );

            return;
        }

        $mapping = $container->getParameter('sulu_document_manager.mapping');

        foreach ($mapping as $key => $item) {
            if ('page' === $item['alias'] || 'home' === $item['alias']) {
                $mapping[$key]['set_default_author'] = $config['default_author'];
            }
        }

        $container->setParameter('sulu_document_manager.mapping', $mapping);
    }
}
