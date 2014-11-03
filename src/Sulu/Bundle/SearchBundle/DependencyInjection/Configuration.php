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

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * Returns the config tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('sulu_search')
            ->children()
                ->scalarNode('adapter_id')->defaultValue('sulu_search.adapter.zend_lucene')->end()
                ->arrayNode('adapters')
                    ->addDefaultsifNotSet()
                    ->children()
                    ->arrayNode('zend_lucene')
                        ->addDefaultsifNotSet()
                        ->children()
                            ->scalarNode('basepath')->defaultValue('%kernel.root_dir%/data')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
