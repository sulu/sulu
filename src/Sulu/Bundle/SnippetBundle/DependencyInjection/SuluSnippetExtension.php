<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\DependencyInjection;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Form\SnippetType;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SuluSnippetExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'field_type_options' => [
                        'selection' => [
                            'snippet' => [
                                'default_type' => 'datagrid_overlay',
                                'resource_key' => 'snippets',
                                'types' => [
                                    'datagrid_overlay' => [
                                        'adapter' => 'table',
                                        'display_properties' => ['title'],
                                        'icon' => 'su-snippet',
                                        'label' => 'sulu_snippet.selection_label',
                                        'overlay_title' => 'sulu_snippet.selection_overlay_title',
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
                ['indexes' => ['snippet' => ['security_context' => 'sulu.global.snippets']]]
            );
        }

        if ($container->hasExtension('sulu_core')) {
            $container->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'paths' => [
                                'snippet' => [
                                    'path' => '%kernel.project_dir%/config/templates/snippets',
                                    'type' => 'snippet',
                                ],
                            ],
                            'default_type' => [
                                'snippet' => 'default',
                            ],
                            'type_map' => ['snippet' => SnippetBridge::class],
                            'resources' => [
                                'snippets' => [
                                    'datagrid' => SnippetDocument::class,
                                    'types' => ['snippet'],
                                    'endpoint' => 'get_snippets',
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_content')) {
            $container->prependExtensionConfig(
                'sulu_content',
                [
                    'search' => [
                        'mapping' => [
                            SnippetDocument::class => ['index' => 'snippet'],
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
                        'snippet' => ['class' => SnippetDocument::class, 'phpcr_type' => 'sulu:snippet', 'form_type' => SnippetType::class],
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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter(
            'sulu_snippet.content-type.default_enabled',
            $config['types']['snippet']['default_enabled']
        );
        $container->setParameter(
            'sulu_snippet.twig.snippet.cache_lifetime',
            $config['twig']['snippet']['cache_lifetime']
        );

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('content.xml');
        $loader->load('snippet.xml');
        $loader->load('export.xml');
        $loader->load('import.xml');
        $loader->load('admin.xml');
    }
}
