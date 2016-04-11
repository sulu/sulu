<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\DependencyInjection;

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

        $treeBuilder->root('sulu_resource')
            ->children()
                ->arrayNode('filters')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('conjunctions')
                            ->useAttributeAsKey('title')
                            ->prototype('array')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('id')->end()
                                    ->scalarNode('translation')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('contexts')
                    ->useAttributeAsKey('title')
                        ->prototype('array')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('fields')->end()
                                ->arrayNode('features')
                                    ->prototype('scalar')->end()
                                ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
