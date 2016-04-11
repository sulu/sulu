<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('sulu_tag')
            ->children()
                ->arrayNode('content')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('types')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('tag_list')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('template')
                                            ->defaultValue('SuluTagBundle:Template:content-types/tag_list.html.twig')
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
