<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use Oro\ORM\Query\AST\Functions\String\GroupConcat;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Component\Rest\Csv\ObjectNotSupportedException;
use Sulu\Component\Rest\DQL\Cast;
use Sulu\Component\Rest\Exception\InvalidHashException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluCoreExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        // process the configuration of SuluCoreExtension
        $configs = $container->getExtensionConfig($this->getAlias());
        $parameterBag = $container->getParameterBag();
        $configs = $parameterBag->resolveValue($configs);
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (isset($config['phpcr']) && $container->hasExtension('doctrine_phpcr')) {
            $phpcrConfig = $config['phpcr'];

            // TODO: Workaround for issue: https://github.com/doctrine/DoctrinePHPCRBundle/issues/178
            if (!isset($phpcrConfig['backend']['check_login_on_server'])) {
                $phpcrConfig['backend']['check_login_on_server'] = false;
            }

            $container->prependExtensionConfig(
                'doctrine_phpcr',
                [
                    'session' => $phpcrConfig,
                ]
            );
        }

        $templatesPath = '%kernel.root_dir%/../vendor/sulu/sulu/src/Sulu/Bundle/CoreBundle/Content/templates';

        $container->prependExtensionConfig(
            'sulu_core',
            [
                'content' => [
                    'structure' => [
                        'paths' => [
                            'sulu' => [
                                'path' => $templatesPath,
                                'type' => 'page',
                            ],
                        ],
                    ],
                ],
            ]
        );

        if ($container->hasExtension('massive_build')) {
            $container->prependExtensionConfig('massive_build', [
                'command_class' => 'Sulu\Bundle\CoreBundle\CommandOptional\SuluBuildCommand',
            ]);
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'routing_loader' => [
                        'default_format' => 'json',
                    ],
                    'exception' => [
                        'enabled' => true,
                        'codes' => [
                            MissingParameterException::class => 400,
                            InvalidHashException::class => 409,
                            ObjectNotSupportedException::class => 406,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('doctrine')) {
            $container->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'gedmo_tree' => [
                                'type' => 'xml',
                                'prefix' => 'Gedmo\\Tree\\Entity',
                                'dir' => '%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity',
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
                        'resolve_target_entities' => [
                            CollectionInterface::class => Collection::class,
                            AccountInterface::class => Account::class,
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('stof_doctrine_extensions')) {
            $container->prependExtensionConfig('stof_doctrine_extensions', ['orm' => ['default' => ['tree' => true]]]);
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig('jms_serializer', ['metadata' => ['debug' => '%kernel.debug%']]);
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig('fos_rest', ['view' => ['formats' => ['json' => true, 'csv' => true]]]);
        }

        if ($container->hasExtension('massive_build')) {
            $container->prependExtensionConfig(
                'massive_build',
                [
                    'targets' => [
                        'prod' => [
                            'dependencies' => [
                                'database' => [],
                                'phpcr' => [],
                                'fixtures' => [],
                                'phpcr_migrations' => [],
                                'system_collections' => [],
                            ],
                        ],
                        'dev' => [
                            'dependencies' => [
                                'database' => [],
                                'fixtures' => [],
                                'phpcr' => [],
                                'user' => [],
                                'phpcr_migrations' => [],
                                'system_collections' => [],
                            ],
                        ],
                        'maintain' => [
                            'dependencies' => [
                                'node_order' => [],
                                'search_index' => [],
                                'phpcr_migrations' => [],
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
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        foreach ($config['locales'] as $locale => $localeName) {
            if (strtolower($locale) !== $locale) {
                throw new InvalidConfigurationException('Invalid locale in configuration: ' . $locale);
            }
        }

        foreach ($config['translations'] as $translation) {
            if (strtolower($translation) !== $translation) {
                throw new InvalidConfigurationException('Invalid translation in configuration: ' . $translation);
            }
        }

        if (strtolower($config['fallback_locale']) !== $config['fallback_locale']) {
            throw new InvalidConfigurationException(
                'Invalid fallback_locale in configuration: ' . $config['fallback_locale']
            );
        }

        $container->setParameter('sulu_core.locales', array_unique(array_keys($config['locales'])));
        $container->setParameter('sulu_core.translated_locales', $config['locales']);
        $container->setParameter('sulu_core.translations', array_unique($config['translations']));
        $container->setParameter('sulu_core.fallback_locale', $config['fallback_locale']);

        $container->setParameter('sulu.cache_dir', $config['cache_dir']);

        $proxyCacheDirectory = $container->getParameterBag()->resolveValue(
            $container->getParameter('sulu.cache_dir') . '/proxies'
        );

        if (!is_dir($proxyCacheDirectory)) {
            mkdir($proxyCacheDirectory, 0777, true);
        }

        $container->setParameter('sulu_core.proxy_cache_dir', $proxyCacheDirectory);

        // Content
        if (isset($config['content'])) {
            $this->initContent($config['content'], $container, $loader);
        }

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

        $loader->load('phpcr.xml');
        $loader->load('rest.xml');
        $loader->load('build.xml');
        $loader->load('localization.xml');
        $loader->load('serializer.xml');
        $loader->load('request_analyzer.xml');
        $loader->load('doctrine.xml');
    }

    /**
     * @param $webspaceConfig
     * @param ContainerBuilder     $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initWebspace($webspaceConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.webspace.config_dir', $webspaceConfig['config_dir']);
        $loader->load('webspace.xml');
    }

    /**
     * @param $fieldsConfig
     * @param ContainerBuilder $container
     */
    private function initFields($fieldsConfig, ContainerBuilder $container)
    {
        $container->setParameter('sulu.fields_defaults.translations', $fieldsConfig['translations']);
        $container->setParameter('sulu.fields_defaults.widths', $fieldsConfig['widths']);
    }

    /**
     * @param $contentConfig
     * @param ContainerBuilder     $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initContent($contentConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        // Default Language
        $container->setParameter('sulu.content.language.namespace', $contentConfig['language']['namespace']);
        $container->setParameter('sulu.content.language.default', $contentConfig['language']['default']);

        // Node names
        $container->setParameter('sulu.content.node_names.base', $contentConfig['node_names']['base']);
        $container->setParameter('sulu.content.node_names.content', $contentConfig['node_names']['content']);
        $container->setParameter('sulu.content.node_names.route', $contentConfig['node_names']['route']);
        $container->setParameter('sulu.content.node_names.snippet', $contentConfig['node_names']['snippet']);

        // Content Types
        $container->setParameter(
            'sulu.content.type.text_line.template',
            $contentConfig['types']['text_line']['template']
        );
        $container->setParameter(
            'sulu.content.type.text_area.template',
            $contentConfig['types']['text_area']['template']
        );
        $container->setParameter(
            'sulu.content.type.text_editor.template',
            $contentConfig['types']['text_editor']['template']
        );
        $container->setParameter(
            'sulu.content.type.resource_locator.template',
            $contentConfig['types']['resource_locator']['template']
        );
        $container->setParameter(
            'sulu.content.type.block.template',
            $contentConfig['types']['block']['template']
        );

        // Default template
        $container->setParameter(
            'sulu.content.structure.default_types',
            $contentConfig['structure']['default_type']
        );
        $container->setParameter(
            'sulu.content.structure.default_type.snippet',
            $contentConfig['structure']['default_type']['snippet']
        );
        $container->setParameter(
            'sulu.content.internal_prefix',
            $contentConfig['internal_prefix']
        );
        $container->setParameter(
            'sulu.content.structure.type_map',
            $contentConfig['structure']['type_map']
        );

        // Template
        $paths = [];
        foreach ($contentConfig['structure']['paths'] as $pathConfig) {
            $pathType = $pathConfig['type'];
            if (!isset($paths[$pathType])) {
                $paths[$pathType] = [];
            }
            $paths[$pathType][] = $pathConfig;
        }

        $container->setParameter('sulu.content.structure.paths', $paths);

        $loader->load('content.xml');
    }

    /**
     * @param $cache
     * @param $container
     * @param $loader
     */
    private function initCache($cache, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.cache.memoize.default_lifetime', $cache['memoize']['default_lifetime']);

        $loader->load('cache.xml');
    }

    /**
     * Initializes list builder.
     *
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initListBuilder(ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $loader->load('list_builder.xml');

        $metadataPaths = $this->getBundleMappingPaths($container->getParameter('kernel.bundles'), 'list-builder');
        $fileLocator = $container->getDefinition('sulu_core.list_builder.metadata.file_locator');
        $fileLocator->replaceArgument(0, $metadataPaths);

        $generalMetadataCacheFolder = $this->createOrGetFolder('%sulu.cache_dir%/list-builder/general', $container);
        $doctrineMetadataCacheFolder = $this->createOrGetFolder('%sulu.cache_dir%/list-builder/doctrine', $container);

        $container->setParameter(
            'sulu_core.list_builder.metadata.provider.general.cache_dir',
            $generalMetadataCacheFolder
        );
        $container->setParameter(
            'sulu_core.list_builder.metadata.provider.doctrine.cache_dir',
            $doctrineMetadataCacheFolder
        );
    }

    /**
     * Create and return directory.
     *
     * @param string $directory
     * @param ContainerBuilder $container
     *
     * @return string
     */
    protected function createOrGetFolder($directory, ContainerBuilder $container)
    {
        $filesystem = new Filesystem();

        $directory = $container->getParameterBag()->resolveValue($directory);
        if (!$filesystem->exists($directory)) {
            $filesystem->mkdir($directory);
        }

        return $directory;
    }

    /**
     * Returns list of bundle config paths.
     *
     * @param string[] $bundles
     * @param string $dir
     *
     * @return array
     */
    private function getBundleMappingPaths($bundles, $dir)
    {
        $metadataPaths = [];
        foreach ($bundles as $bundle) {
            $refl = new \ReflectionClass($bundle);
            $path = dirname($refl->getFilename());

            foreach (['Entity', 'Document', 'Model'] as $entityNamespace) {
                if (!file_exists($path . '/' . $entityNamespace)) {
                    continue;
                }

                $namespace = $refl->getNamespaceName() . '\\' . $entityNamespace;
                $finalPath = implode('/', [$path, 'Resources', 'config', $dir]);
                if (!file_exists($finalPath)) {
                    continue;
                }

                $metadataPaths[$namespace] = $finalPath;
            }
        }

        return $metadataPaths;
    }
}
