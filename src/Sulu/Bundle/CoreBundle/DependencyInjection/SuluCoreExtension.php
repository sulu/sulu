<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluCoreExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
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

            foreach ($container->getExtensions() as $name => $extension) {
                $prependConfig = array();
                switch ($name) {
                    case 'doctrine_phpcr':
                        $prependConfig = array(
                            'session' => $phpcrConfig,
                            'odm' => array(),
                        );
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
            $container->prependExtensionConfig('massive_build', array(
                'command_class' => 'Sulu\Bundle\CoreBundle\CommandOptional\SuluBuildCommand'
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('sulu.cache_dir', $config['cache_dir']);

        // PHPCR
        if (isset($config['phpcr'])) {
            $this->initPhpcr($config['phpcr'], $container, $loader);
        }

        // Content
        if (isset($config['content'])) {
            $this->initContent($config['content'], $container, $loader);
        }

        // Webspace
        if (isset($config['webspace'])) {
            $this->initWebspace($config['webspace'], $container, $loader);
        }

        // Http Cache
        if (isset($config['http_cache'])) {
            $this->initHttpCache($config['http_cache'], $container, $loader);
        }

        // Default Fields
        if (isset($config['fields_defaults'])) {
            $this->initFields($config['fields_defaults'], $container);
        }

        $loader->load('rest.xml');
        $loader->load('build.xml');
    }

    /**
     * @param $webspaceConfig
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initWebspace($webspaceConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.webspace.config_dir', $webspaceConfig['config_dir']);
        $container->setParameter(
            'sulu_core.webspace.request_analyzer.enabled',
            $webspaceConfig['request_analyzer']['enabled']
        );
        $container->setParameter(
            'sulu_core.webspace.request_analyzer.priority',
            $webspaceConfig['request_analyzer']['priority']
        );
        $loader->load('webspace.xml');
    }

    private function initHttpCache($httpCacheConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.http_cache.type', $httpCacheConfig['type']);
        $loader->load('http-cache.xml');
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
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     * @throws InvalidArgumentException
     */
    private function initPhpcr($phpcrConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $loader->load('phpcr.xml');
    }

    /**
     * @param $contentConfig
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initContent($contentConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        // Default template
        $container->setParameter('sulu.content.template.default', $contentConfig['default_template']['page']);
        $container->setParameter('sulu.content.type.default', $contentConfig['default_template']['snippet']);
        $container->setParameter('sulu.content.internal_prefix', $contentConfig['internal_prefix']);

        // Default Language
        $container->setParameter('sulu.content.language.namespace', $contentConfig['language']['namespace']);
        $container->setParameter('sulu.content.language.default', $contentConfig['language']['default']);

        // Node names
        $container->setParameter('sulu.content.node_names.base', $contentConfig['node_names']['base']);
        $container->setParameter('sulu.content.node_names.content', $contentConfig['node_names']['content']);
        $container->setParameter('sulu.content.node_names.route', $contentConfig['node_names']['route']);
        $container->setParameter('sulu.content.node_names.temp', $contentConfig['node_names']['temp']);
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

        // Template
        $container->setParameter(
            'sulu.content.structure.paths',
            $contentConfig['structure']['paths']
        );

        $loader->load('content.xml');
    }
}
