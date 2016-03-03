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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluContentExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_core')) {
            $prepend = [
                'content' => [
                    'structure' => [
                        'paths' => [
                            [
                                'path' => __DIR__ . '/../Content/templates',
                                'type' => 'page',
                            ],
                        ],
                    ],
                ],
            ];

            $container->prependExtensionConfig('sulu_core', $prepend);
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'directories' => [
                            [
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Bundle\ContentBundle',
                            ],
                            [
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\Content',
                            ],
                            [
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
                            'Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException' => 409,
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

        $this->processDocumentManager($container, $config);
        $this->processTemplates($container, $config);
        $this->processPreview($container, $config);

        if (isset($bundles['SuluSearchBundle'])) {
            $this->processSearch($config, $loader, $container);
        }

        $loader->load('services.xml');
        $loader->load('smart_content.xml');
        $loader->load('content_types.xml');
        $loader->load('preview.xml');
        $loader->load('structure.xml');
        $loader->load('extension.xml');
        $loader->load('form.xml');
        $loader->load('compat.xml');
        $loader->load('document.xml');
        $loader->load('serializer.xml');
    }

    private function processDocumentManager(ContainerBuilder $container, $config)
    {
        $container->setParameter('sulu.content.publish.document_manager', $config['publish']['document_manager']);
    }

    private function processPreview(ContainerBuilder $container, $config)
    {
        $container->setParameter('sulu.content.preview.mode', $config['preview']['mode']);
        $container->setParameter('sulu.content.preview.websocket', $config['preview']['websocket']);
        $container->setParameter('sulu.content.preview.delay', $config['preview']['delay']);
        $errorTemplate = null;
        if (isset($config['preview']['error_template'])) {
            $errorTemplate = $config['preview']['error_template'];
        }
        $container->setParameter(
            'sulu.content.preview.error_template',
            $errorTemplate
        );
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
    }

    private function processSearch($config, LoaderInterface $loader, ContainerBuilder $container)
    {
        $container->setParameter('sulu_content.search.mapping', $config['search']['mapping']);
        $loader->load('search.xml');
    }
}
