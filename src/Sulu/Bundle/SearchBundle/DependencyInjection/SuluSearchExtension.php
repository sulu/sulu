<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluSearchExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig('jms_serializer', array(
            'metadata' => array(
                'directories' => array(
                    array(
                        'path' => realpath(__DIR__ . '/..') . '/Resources/config/serializer',
                        'namespace_prefix' => 'Massive\Bundle\SearchBundle\Search',
                    ),
                ),
            ),
        ));

        $container->prependExtensionConfig('massive_search', array(
            'services' => array(
                'factory' => 'sulu_search.search.factory',
            ),
        ));
    }

    /**
     * {@inheritDoc}

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('sulu_search.structure_index_name', $config['structure_index_name']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('metadata.xml');
        $loader->load('search.xml');
        $loader->load('build.xml');

        if ($container->hasParameter('sulu.context') && 'website' == $container->getParameter('sulu.context')) {
            $loader->load('website.xml');
        }
    }
}
