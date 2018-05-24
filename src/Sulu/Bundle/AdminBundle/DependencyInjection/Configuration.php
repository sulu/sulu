<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root('sulu_admin')
            ->children()
                ->scalarNode('name')->defaultValue('Sulu Admin')->end()
                ->scalarNode('email')->isRequired()->end()
                ->scalarNode('user_data_service')->defaultValue('sulu_security.user_manager')->end()
                ->arrayNode('widget_groups')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('mappings')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('resources')
                    ->useAttributeAsKey('resourceKey')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('form')
                                ->prototype('scalar')->end()
                                ->isRequired()
                            ->end()
                            ->scalarNode('datagrid')->end()
                            ->scalarNode('endpoint')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('field_type_options')
                    ->children()
                        ->arrayNode('assignment')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('adapter')->isRequired()->end()
                                    ->arrayNode('displayProperties')
                                        ->isRequired()
                                        ->requiresAtLeastOneElement()
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                    ->scalarNode('icon')->isRequired()->end()
                                    ->scalarNode('label')->isRequired()->end()
                                    ->scalarNode('overlayTitle')->isRequired()->end()
                                    ->scalarNode('resourceKey')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('single_selection')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('displayProperty')->isRequired()->end()
                                    ->arrayNode('searchProperties')
                                        ->isRequired()
                                        ->requiresAtLeastOneElement()
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                    ->scalarNode('resourceKey')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
