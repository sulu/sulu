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
                        ->arrayNode('selection')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('default_type')
                                        ->isRequired()
                                        ->validate()
                                            ->ifNotInArray(['datagrid', 'overlay'])
                                            ->thenInvalid('Invalid selection type "%s"')
                                        ->end()
                                    ->end()
                                    ->scalarNode('resource_key')->isRequired()->end()
                                    ->arrayNode('types')
                                        ->children()
                                            // TODO allow only "datagrid" or "overlay" or allow multiple to change using schemaOptions
                                            ->arrayNode('datagrid')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('overlay')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                    ->arrayNode('display_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('icon')->isRequired()->end()
                                                    ->scalarNode('label')->isRequired()->end()
                                                    ->scalarNode('overlay_title')->isRequired()->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('single_selection')
                            // TODO update in the same way as selection config option (snake case and default_type)
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->arrayNode('auto_complete')
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
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
