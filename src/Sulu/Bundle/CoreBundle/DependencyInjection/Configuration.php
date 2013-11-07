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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_core');

        $rootNode
            ->children()
                ->arrayNode('phpcr')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('factory_class')
                            ->defaultValue('Jackalope\RepositoryFactoryJackrabbit')
                        ->end()
                        ->scalarNode('url')
                            ->defaultValue('http://localhost:8080/server')
                        ->end()
                        ->scalarNode('username')
                            ->defaultValue('admin')
                        ->end()
                        ->scalarNode('password')
                            ->defaultValue('admin')
                        ->end()
                        ->scalarNode('workspace')
                            ->defaultValue('default')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('content')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('base_path')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('content')
                                    ->defaultValue('/cmf/contents')
                                ->end()
                                ->scalarNode('route')
                                    ->defaultValue('/cmf/routes')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('types')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('text_line')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('template')
                                            ->defaultValue('SuluContentBundle:Template:content-types/text_line.html.twig')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('text_area')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('template')
                                            ->defaultValue('SuluContentBundle:Template:content-types/text_area.html.twig')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('resource_locator')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('template')
                                            ->defaultValue('SuluContentBundle:Template:content-types/resource_locator.html.twig')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('templates')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('default_path')
                                    ->defaultValue('%kernel.root_dir%/../Resources/templates')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
