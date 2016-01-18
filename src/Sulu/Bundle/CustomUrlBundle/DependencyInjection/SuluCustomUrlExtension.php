<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\DependencyInjection;

use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Loads configuration and services for custom-urls.
 */
class SuluCustomUrlExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_document_manager')) {
            $container->prependExtensionConfig(
                'sulu_document_manager',
                [
                    'mapping' => [
                        'custom_urls' => [
                            'class' => CustomUrlDocument::class,
                            'phpcr_type' => 'sulu:custom-url',
                            'mapping' => [
                                'title' => ['property' => 'title'],
                                'published' => ['property' => 'published'],
                                'baseDomain' => ['property' => 'baseDomain'],
                                'domainParts' => ['property' => 'domainParts', 'type' => 'json_array'],
                                'target' => ['property' => 'target', 'type' => 'reference'],
                                'multilingual' => ['property' => 'multilingual'],
                                'canonical' => ['property' => 'canonical'],
                                'redirect' => ['property' => 'redirect'],
                                'targetLocale' => ['property' => 'targetLocale'],
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
