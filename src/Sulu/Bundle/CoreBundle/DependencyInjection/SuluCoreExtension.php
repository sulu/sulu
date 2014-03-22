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
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
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
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        // PHPCR
        if (isset($config['phpcr'])) {
            $this->initPhpcr($config['phpcr'], $container, $loader);
        }

        // Content
        if (isset($config['content'])) {
            $this->initContent($config['content'], $container, $loader);
        }

        // Portal
        if (isset($config['webspace'])) {
            $this->initWebspace($config['webspace'], $container, $loader);
        }

        // Default Fields
        if (isset($config['fields_defaults'])) {
            $this->initFields($config['fields_defaults'], $container);
        }

        $loader->load('rest.xml');
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
        $type = isset($phpcrConfig['backend']['type']) ? $phpcrConfig['backend']['type'] : 'jackrabbit';

        switch ($type) {
            case 'doctrinedbal':
                $phpcrConfig = $this->loadDoctrineDbalParameters($phpcrConfig, $container);
                $xmlFile = 'phpcr_doctrinedbal.xml';
                break;
            case 'jackrabbit':
                $phpcrConfig = $this->loadJackrabbitParameters($phpcrConfig);
                $xmlFile = 'phpcr_jackrabbit.xml';
                break;
            case 'midgard2':
                $phpcrConfig = $this->loadMidgard2Parameters($phpcrConfig);
                $xmlFile = 'phpcr_midgard2.xml';
                break;
            default:
                throw new InvalidArgumentException(sprintf('You set an unsupported transport type "%s"', $type));
        }

        // set backend parameter
        foreach ($phpcrConfig['backend'] as $name => $backendParameter) {
            $container->setParameter(sprintf('sulu.phpcr.backend.%s', $name), $backendParameter);
        }
        // set session parameter
        $container->setParameter('sulu.phpcr.username', $phpcrConfig['username']);
        $container->setParameter('sulu.phpcr.password', $phpcrConfig['password']);
        $container->setParameter('sulu.phpcr.workspace', $phpcrConfig['workspace']);

        // default session configuration
        $loader->load('phpcr.xml');
        //specific session configuration
        $loader->load($xmlFile);
    }

    private function loadDoctrineDbalParameters($phpcrConfig, ContainerBuilder $container)
    {
        $connectionName = isset($phpcrConfig['backend']['connection'])
            ? $phpcrConfig['backend']['connection']
            : null;

        $connectionService = $connectionName
            ? sprintf('doctrine.dbal.%s_connection', $connectionName)
            : 'database_connection';

        $phpcrConfig['backend']['connection'] = $connectionService;

        $container
            ->setDefinition('sulu.phpcr.session', new DefinitionDecorator('sulu.phpcr.session'))
            ->replaceArgument(
                2,
                array(
                    'jackalope.doctrine_dbal_connection' => new Reference($connectionService)
                )
            );

        return $phpcrConfig;
    }

    private function loadJackrabbitParameters($phpcrConfig)
    {
        // default values
        if (!isset($phpcrConfig['url'])) {
            $phpcrConfig['url'] = 'http://localhost:8080/server';
        }

        return $phpcrConfig;
    }

    private function loadMidgard2Parameters($phpcrConfig)
    {
        return $phpcrConfig;
    }

    /**
     * @param $contentConfig
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initContent($contentConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        // Default template
        $container->setParameter('sulu.content.template.default', $contentConfig['default_template']);

        // Default Language
        $container->setParameter('sulu.content.language.namespace', $contentConfig['language']['namespace']);
        $container->setParameter('sulu.content.language.default', $contentConfig['language']['default']);

        // Node names
        $container->setParameter('sulu.content.node_names.base', $contentConfig['node_names']['base']);
        $container->setParameter('sulu.content.node_names.content', $contentConfig['node_names']['content']);
        $container->setParameter('sulu.content.node_names.route', $contentConfig['node_names']['route']);

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
            'sulu.content.type_prefix',
            $contentConfig['type_prefix']
        );

        // Template
        $container->setParameter(
            'sulu.content.template.default_path',
            $contentConfig['templates']['default_path']
        );

        $loader->load('content.xml');
    }
}
