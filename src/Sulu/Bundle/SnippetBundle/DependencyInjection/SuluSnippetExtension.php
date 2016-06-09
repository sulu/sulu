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
                                [
                                    'path' => __DIR__ . '/../Content/templates',
                                    'type' => 'page',
                                ],
                            ],
                            'type_map' => ['snippet' => SnippetBridge::class],
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
                        'snippet' => ['class' => SnippetDocument::class, 'phpcr_type' => 'sulu:snippet'],
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
            'sulu_snippet.content-type.template',
            $config['types']['snippet']['template']
        );
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
        $loader->load('admin.xml');
    }
}
