<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;
use Sulu\Bundle\AdminBundle\Exception\MetadataNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\MetadataProviderNotFoundException;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RemoveForeignContextServicesPass;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluAdminExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('framework')) {
            $publicDir = 'public';

            $composerFile = $container->getParameter('kernel.project_dir') . '/composer.json';
            if (file_exists($composerFile)) {
                $composerConfig = json_decode(file_get_contents($composerFile), true);
                $publicDir = $composerConfig['extra']['public-dir'] ?? $publicDir;
            }

            $container->prependExtensionConfig(
                'framework',
                [
                    'assets' => [
                        'packages' => [
                            'sulu_admin' => [
                                'json_manifest_path' => '%kernel.project_dir%/' . $publicDir . '/build/admin/manifest.json',
                            ],
                        ],
                    ],
                    'web_link' => [
                        'enabled' => true,
                    ],
                    'translator' => [
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

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            MetadataNotFoundException::class => 404,
                            MetadataProviderNotFoundException::class => 404,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('fos_js_routing')) {
            $container->prependExtensionConfig(
                'fos_js_routing',
                [
                    'routes_to_expose' => [
                        'c?get_.*',
                    ],
                ]
            );
        }

        $container->prependExtensionConfig(
            'sulu_admin',
            [
                'resources' => [
                    'localizations' => [
                        'routes' => [
                            'list' => 'get_localizations',
                        ],
                    ],
                ],
            ]
        );
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

        $container->setParameter($this->getAlias() . '.forms.directories', $config['forms']['directories'] ?? []);
        $container->setParameter($this->getAlias() . '.lists.directories', $config['lists']['directories'] ?? []);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->registerForAutoconfiguration(Admin::class)
            ->addTag(AddAdminPass::ADMIN_TAG)
            ->addTag(RemoveForeignContextServicesPass::SULU_CONTEXT_TAG, ['context' => SuluKernel::CONTEXT_ADMIN]);

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
