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

        // PHPCR
        $container->setParameter('sulu.phpcr.factory_class', $config['phpcr']['factory_class']);
        $container->setParameter('sulu.phpcr.url', $config['phpcr']['url']);
        $container->setParameter('sulu.phpcr.username', $config['phpcr']['username']);
        $container->setParameter('sulu.phpcr.password', $config['phpcr']['password']);
        $container->setParameter('sulu.phpcr.workspace', $config['phpcr']['workspace']);

        // Base Path
        $container->setParameter('sulu.content.base_path.content', $config['content']['base_path']['content']);
        $container->setParameter('sulu.content.base_path.route', $config['content']['base_path']['route']);

        // Content Types
        $container->setParameter('sulu.content.type.text_line.template', $config['content']['types']['text_line']['template']);
        $container->setParameter('sulu.content.type.text_area.template', $config['content']['types']['text_area']['template']);
        $container->setParameter('sulu.content.type.resource_locator.template', $config['content']['types']['resource_locator']['template']);

        // Template
        $container->setParameter('sulu.content.template.default_path', $config['content']['templates']['default_path']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('content.xml');
        $loader->load('portal.xml');
        $loader->load('phpcr.xml');
        $loader->load('rest.xml');
    }
}
