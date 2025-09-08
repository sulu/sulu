<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use Gedmo\Exception;
use Oro\ORM\Query\AST\Functions\Cast;
use Oro\ORM\Query\AST\Functions\String\GroupConcat;
use Sulu\Bundle\CoreBundle\CommandOptional\SuluBuildCommand;
use Sulu\Component\Rest\Csv\ObjectNotSupportedException;
use Sulu\Component\Rest\Exception\InsufficientDescendantPermissionsException;
use Sulu\Component\Rest\Exception\InvalidHashException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ReferencingResourcesFoundExceptionInterface;
use Sulu\Component\Rest\Exception\RemoveDependantResourcesFoundExceptionInterface;
use Sulu\Component\Rest\ListBuilder\Filter\InvalidFilterTypeOptionsException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluCoreExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // process the configuration of SuluCoreExtension
        $configs = $container->getExtensionConfig($this->getAlias());
        $parameterBag = $container->getParameterBag();
        $configs = $parameterBag->resolveValue($configs);
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->prependExtensionConfig(
            'sulu_admin',
            [
                'templates' => [
                    'block' => [ // TODO maybe blocks?
                        'default_type' => null,
                        'directories' => [
                            'app' => '%kernel.project_dir%/config/templates/blocks',
                        ],
                    ],
                ],
            ]
        );

        if ($container->hasExtension('massive_build')) {
            $container->prependExtensionConfig('massive_build', [
                'command_class' => SuluBuildCommand::class,
            ]);
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'enabled' => true,
                        'map_exception_codes' => true,
                        'codes' => [
                            MissingParameterException::class => 400,
                            InvalidHashException::class => 409,
                            ObjectNotSupportedException::class => 406,
                            InvalidFilterTypeOptionsException::class => 400,
                            RemoveDependantResourcesFoundExceptionInterface::class => 409,
                            InsufficientDescendantPermissionsException::class => 403,
                            ReferencingResourcesFoundExceptionInterface::class => 409,
                        ],
                        'exception_listener' => false,
                        'serialize_exceptions' => false,
                        'flatten_exception_format' => 'legacy',
                    ],
                    'serializer' => [
                        'serialize_null' => true,
                    ],
                    'body_listener' => [
                        'enabled' => true,
                    ],
                    'routing_loader' => false,
                    'view' => [
                        'formats' => [
                            'json' => true,
                            'csv' => true,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('handcraftedinthealps_rest_routing')) {
            $container->prependExtensionConfig(
                'handcraftedinthealps_rest_routing',
                [
                    'routing_loader' => [
                        'default_format' => 'json',
                        'formats' => [
                            'json' => true,
                            'csv' => true,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'property_naming' => [
                        'id' => 'jms_serializer.identical_property_naming_strategy',
                    ],
                ]
            );
        }

        if ($container->hasExtension('doctrine')) {
            // depending on the gedmo version it is in gedmo lib or src folder
            // we use the autoloader to detect the correct directory
            // see also: https://github.com/sulu/sulu/pull/5753
            $reflection = new \ReflectionClass(Exception::class);
            $gedmoDirectory = \dirname($reflection->getFileName());

            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'gedmo_tree' => [
                                'type' => 'xml',
                                'prefix' => 'Gedmo\\Tree\\Entity',
                                'dir' => $gedmoDirectory . '/Tree/Entity',
                                'alias' => 'GedmoTree',
                                'is_bundle' => false,
                            ],
                        ],
                        'dql' => [
                            'string_functions' => [
                                'group_concat' => GroupConcat::class,
                                'CAST' => Cast::class,
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('stof_doctrine_extensions')) {
            $container->prependExtensionConfig('stof_doctrine_extensions', ['orm' => ['default' => ['tree' => true]]]);
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'debug' => '%kernel.debug%',
                    ],
                    'handlers' => [
                        'datetime' => [
                            'default_format' => 'Y-m-d\\TH:i:s',
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('massive_build')) {
            $massiveBuildConfig = [
                'targets' => [
                    'prod' => [
                        'dependencies' => [
                            'database' => [],
                            'fixtures' => [],
                            'system_collections' => [],
                            'security' => [],
                        ],
                    ],
                    'dev' => [
                        'dependencies' => [
                            'database' => [],
                            'fixtures' => [],
                            'user' => [],
                            'system_collections' => [],
                            'security' => [],
                        ],
                    ],
                    'maintain' => [
                        'dependencies' => [
                            'node_order' => [],
                        ],
                    ],
                ],
            ];

            // Check sulu page bundle enabled
            /** @var array<string, string> $bundles */
            $bundles = $container->getParameter('kernel.bundles');
            $contentPageBundle = \array_key_exists('SuluNextPageBundle', $bundles) ? $bundles['SuluNextPageBundle'] : null;
            if ($contentPageBundle) {
                $massiveBuildConfig['targets']['prod']['dependencies']['homepage'] = [];
                $massiveBuildConfig['targets']['dev']['dependencies']['homepage'] = [];
            }

            $container->prependExtensionConfig(
                'massive_build',
                $massiveBuildConfig
            );
        }

        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig(
                'framework',
                [
                    'cache' => [
                        'directory' => '%sulu.common_cache_dir%/pools',
                    ],
                ]
            );
        }
    }

    /**
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        foreach ($config['locales'] as $locale => $localeName) {
            if (\strtolower($locale) !== $locale) {
                throw new InvalidConfigurationException('Invalid locale in configuration: ' . $locale);
            }
        }

        foreach ($config['translations'] as $translation) {
            if (\strtolower($translation) !== $translation) {
                throw new InvalidConfigurationException('Invalid translation in configuration: ' . $translation);
            }
        }

        if (\strtolower($config['fallback_locale']) !== $config['fallback_locale']) {
            throw new InvalidConfigurationException(
                'Invalid fallback_locale in configuration: ' . $config['fallback_locale']
            );
        }

        $container->setParameter('sulu_core.locales', \array_unique(\array_keys($config['locales'])));
        $container->setParameter('sulu_core.translated_locales', $config['locales']);
        $container->setParameter('sulu_core.translations', \array_unique($config['translations']));
        $container->setParameter('sulu_core.fallback_locale', $config['fallback_locale']);

        $container->setParameter('sulu.cache_dir', $config['cache_dir']);

        $proxyCacheDirectory = $container->getParameterBag()->resolveValue(
            $container->getParameter('sulu.cache_dir') . '/proxies'
        );

        if (!\is_dir($proxyCacheDirectory)) {
            \mkdir($proxyCacheDirectory, 0777, true);
        }

        $container->setParameter('sulu_core.proxy_cache_dir', $proxyCacheDirectory);

        // Webspace
        if (isset($config['webspace'])) {
            $this->initWebspace($config['webspace'], $container, $loader);
        }

        // Cache
        if (isset($config['cache'])) {
            $this->initCache($config['cache'], $container, $loader);
        }

        // Default Fields
        if (isset($config['fields_defaults'])) {
            $this->initFields($config['fields_defaults'], $container);
        }

        $this->initListBuilder($container, $loader);

        $loader->load('rest.xml');
        $loader->load('build.xml');
        $loader->load('localization.xml');
        $loader->load('serializer.xml');
        $loader->load('request_analyzer.xml');
        $loader->load('doctrine.xml');
    }

    /**
     * @return void
     */
    private function initWebspace(array $webspaceConfig, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.webspace.config_dir', $webspaceConfig['config_dir']);

        $loader->load('webspace.xml');
    }

    /**
     * @return void
     */
    private function initFields(array $fieldsConfig, ContainerBuilder $container)
    {
        $container->setParameter('sulu.fields_defaults.translations', $fieldsConfig['translations']);
        $container->setParameter('sulu.fields_defaults.widths', $fieldsConfig['widths']);
    }

    /**
     * @return void
     */
    private function initCache(array $cache, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.cache.memoize.default_lifetime', $cache['memoize']['default_lifetime']);

        $loader->load('cache.xml');
    }

    /**
     * Initializes list builder.
     *
     * @return void
     */
    private function initListBuilder(ContainerBuilder $container, XmlFileLoader $loader)
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (\array_key_exists('SuluAdminBundle', $bundles)) {
            $loader->load('list_builder.xml');
        }
    }
}
