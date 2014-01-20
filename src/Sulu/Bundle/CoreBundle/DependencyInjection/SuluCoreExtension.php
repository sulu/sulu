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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluCoreExtension extends Extension
{
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
        if (isset($config['portal'])) {
            $this->initWorkspace($config['portal'], $container, $loader);
        }

        $loader->load('rest.xml');
    }

    /**
     * @param $workspaceConfig
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initWorkspace($workspaceConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        $container->setParameter('sulu_core.workspace.config_dir', $workspaceConfig['config_dir']);
        $loader->load('workspace.xml');
    }

    /**
     * @param $phpcrConfig
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initPhpcr($phpcrConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        // session factory
        $container->setParameter('sulu.phpcr.factory_class', $phpcrConfig['factory_class']);
        $container->setParameter('sulu.phpcr.url', $phpcrConfig['url']);
        $container->setParameter('sulu.phpcr.username', $phpcrConfig['username']);
        $container->setParameter('sulu.phpcr.password', $phpcrConfig['password']);
        $container->setParameter('sulu.phpcr.workspace', $phpcrConfig['workspace']);

        $loader->load('phpcr.xml');
    }

    /**
     * @param $contentConfig
     * @param ContainerBuilder $container
     * @param Loader\XmlFileLoader $loader
     */
    private function initContent($contentConfig, ContainerBuilder $container, Loader\XmlFileLoader $loader)
    {
        // Base Path
        $container->setParameter('sulu.content.base_path.content', $contentConfig['base_path']['content']);
        $container->setParameter('sulu.content.base_path.route', $contentConfig['base_path']['route']);

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

        // Template
        $container->setParameter(
            'sulu.content.template.default_path',
            $contentConfig['templates']['default_path']
        );

        $loader->load('content.xml');
    }
}
