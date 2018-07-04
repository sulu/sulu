<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluAdminExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig(
                'framework',
                [
                    'assets' => [
                        'packages' => [
                            'sulu_admin' => [
                                'json_manifest_path' => '%kernel.project_dir%/public/admin/build/manifest.json',
                            ],
                        ],
                    ],
                    'web_link' => [
                        'enabled' => true,
                    ],
                    'translator' =>  [
                        'enabled' => true,
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
                                'name' => 'sulu_admin',
                                'path' => __DIR__ . '/../Resources/config/serializer',
                                'namespace_prefix' => 'Sulu\Component\SmartContent\Configuration',
                            ],
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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter($this->getAlias() . '.name', $config['name']);
        $container->setParameter($this->getAlias() . '.email', $config['email']);
        $container->setParameter($this->getAlias() . '.user_data_service', $config['user_data_service']);
        $container->setParameter($this->getAlias() . '.resources', $config['resources']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->loadFieldTypeOptions(
            $config['field_type_options'],
            $container->getDefinition('sulu_admin.field_type_option_registry')
        );
    }

    public function loadFieldTypeOptions(
        array $fieldTypeOptionsConfig,
        Definition $fieldTypeOptionRegistry
    ) {
        foreach ($fieldTypeOptionsConfig as $baseFieldType => $baseFieldTypeConfig) {
            foreach ($baseFieldTypeConfig as $fieldTypeName => $fieldTypeConfig) {
                $fieldTypeOptionRegistry->addMethodCall('add', [$fieldTypeName, $baseFieldType, $fieldTypeConfig]);
            }
        }
    }
}
