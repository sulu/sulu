<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CollaborationBundle\DependencyInjection;

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
        $root = $treeBuilder->root('sulu_collaboration');

        $root->children()
            ->scalarNode('interval')
                ->defaultValue(300000)
                ->info('Defines the interval in milliseconds between the keep alive messages for the collaboration feature')
            ->end()
            ->scalarNode('threshold')
                ->defaultValue(10000)
                ->info('Defines the threshold after which the collabaration without keep alive signal is considered finished')
            ->end()
            ->scalarNode('entity_cache')
                ->info('The service which will be used for caching collaborations grouped by entities')
            ->end()
            ->scalarNode('connection_cache')
                ->info('The service which will be used for caching collaborations grouped by connections')
            ->end()
        ->end();

        return $treeBuilder;
    }
}
