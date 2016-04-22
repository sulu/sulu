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

use InvalidArgumentException;
use Sulu\Component\Rest\Csv\ObjectNotSupportedException;
use Sulu\Component\Rest\Exception\InvalidHashException;
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

        if (isset($config['phpcr'])) {
            $phpcrConfig = $config['phpcr'];

            // TODO: Workaround for issue: https://github.com/doctrine/DoctrinePHPCRBundle/issues/178
            if (!isset($phpcrConfig['backend']['check_login_on_server'])) {
                $phpcrConfig['backend']['check_login_on_server'] = false;
            }

            foreach (array_keys($container->getExtensions()) as $name) {
                $prependConfig = [];
                switch ($name) {
                    case 'doctrine_phpcr':
                        $prependConfig = [
                            'session' => $phpcrConfig,
                            'odm' => [],
                        ];
                        break;
                    case 'cmf_core':
                        break;
                }

                if ($prependConfig) {
                    $container->prependExtensionConfig($name, $prependConfig);
                }
            }
        }

        if ($container->hasExtension('massive_build')) {
            $container->prependExtensionConfig('massive_build', [
                'command_class' => 'Sulu\Bundle\CoreBundle\CommandOptional\SuluBuildCommand',
            ]);
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'enabled' => true,
                        'codes' => [
                            InvalidHashException::class => 409,
                            ObjectNotSupportedException::class => 406,
                        ],
                    ],
                    'service' => [
                        'exception_handler' => 'sulu_core.rest.exception_wrapper_handler',
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

        $container->setParameter('sulu_core.locales', array_unique(array_keys($config['locales'])));
        $container->setParameter('sulu_core.translated_locales', $config['locales']);
        $container->setParameter('sulu_core.translations', array_unique($config['translations']));
        $container->setParameter('sulu_core.fallback_locale', $config['fallback_locale']);

        $container->setParameter('sulu.cache_dir', $config['cache_dir']);

        // PHPCR
        if (isset($config['phpcr'])) {
            $this->initPhpcr($config['phpcr'], $container, $loader);
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

        $loader->load('rest.xml');
        $loader->load('build.xml');
        $loader->load('localization.xml');
        $loader->load('serializer.xml');
        $loader->load('request_analyzer.xml');
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
     * @param $phpcrConfig
     * @param ContainerBuilder     $container
     * @param Loader\XmlFileLoader $loader
     *
     * @throws InvalidArgumentException
     */
    private function initPhpcr($phpcrConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $loader->load('phpcr.xml');
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

        $container->setParameter('sulu_core.list_builder.metadata.provider.general.cache_dir', $generalMetadataCacheFolder);
        $container->setParameter('sulu_core.list_builder.metadata.provider.doctrine.cache_dir', $doctrineMetadataCacheFolder);
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
