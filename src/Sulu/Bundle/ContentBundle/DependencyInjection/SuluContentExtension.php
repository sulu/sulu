<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Bundle\ContentBundle\Form\Type\HomeDocumentType;
use Sulu\Bundle\ContentBundle\Form\Type\PageDocumentType;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluContentExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'field_type_options' => [
                        'selection' => [
                            'internal_links' => [
                                'adapter' => 'column_list',
                                'displayProperties' => ['title'],
                                'icon' => 'su-document',
                                'label' => 'sulu_content.selection_label',
                                'resourceKey' => 'pages',
                                'overlayTitle' => 'sulu_content.selection_overlay_title',
                            ],
                        ],
                    ],
                ]
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
                            'type_map' => [
                                'page' => PageBridge::class,
                                'home' => PageBridge::class,
                            ],
                            'resources' => [
                                'pages' => [
                                    'datagrid' => BasePageDocument::class,
                                    'types' => ['page', 'home'],
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
                                'name' => 'sulu_content',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Bundle\ContentBundle',
                            ],
                            [
                                'name' => 'sulu_content.component.content',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\Content',
                            ],
                            [
                                'name' => 'sulu_content.component.webspace',
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
                'sulu_content',
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

        $this->processTemplates($container, $config);

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

        if (array_key_exists('SuluAutomationBundle', $bundles)) {
            $loader->load('automation.xml');
        }

        if (array_key_exists('SuluAudienceTargetingBundle', $bundles)) {
            $loader->load('rule.xml');
        }

        $this->appendDefaultAuthor($config, $container);

        $container->setParameter('sulu_content.seo', $config['seo']);
    }

    private function processTemplates(ContainerBuilder $container, $config)
    {
        $container->setParameter(
            'sulu.content.type.smart_content.template',
            $config['types']['smart_content']['template']
        );
        $container->setParameter(
            'sulu.content.type.internal_links.template',
            $config['types']['internal_links']['template']
        );
        $container->setParameter(
            'sulu.content.type.single_internal_link.template',
            $config['types']['single_internal_link']['template']
        );
        $container->setParameter(
            'sulu.content.type.phone.template',
            $config['types']['phone']['template']
        );
        $container->setParameter(
            'sulu.content.type.password.template',
            $config['types']['password']['template']
        );
        $container->setParameter(
            'sulu.content.type.url.template',
            $config['types']['url']['template']
        );
        $container->setParameter(
            'sulu.content.type.email.template',
            $config['types']['email']['template']
        );
        $container->setParameter(
            'sulu.content.type.date.template',
            $config['types']['date']['template']
        );
        $container->setParameter(
            'sulu.content.type.time.template',
            $config['types']['time']['template']
        );
        $container->setParameter(
            'sulu.content.type.color.template',
            $config['types']['color']['template']
        );
        $container->setParameter(
            'sulu.content.type.checkbox.template',
            $config['types']['checkbox']['template']
        );
        $container->setParameter(
            'sulu.content.type.multiple_select.template',
            $config['types']['multiple_select']['template']
        );
        $container->setParameter(
            'sulu.content.type.single_select.template',
            $config['types']['single_select']['template']
        );
        $container->setParameter(
            'sulu.content.type.teaser_selection.template',
            $config['types']['teaser_selection']['template']
        );
    }

    private function processSearch($config, LoaderInterface $loader, ContainerBuilder $container)
    {
        $container->setParameter('sulu_content.search.mapping', $config['search']['mapping']);
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
        $container->setParameter('sulu_content.default_author', $config['default_author']);
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
