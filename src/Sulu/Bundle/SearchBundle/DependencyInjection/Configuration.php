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
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('structure_index_name')
                    ->info('Name of the search index to use for structures (this should change depending on the sulu context)')
                    ->defaultValue('content')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
