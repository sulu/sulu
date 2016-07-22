<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * {@inheritdoc}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_websocket');

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->arrayNode('server')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('ip_address')->defaultValue('0.0.0.0')->end()
                        ->scalarNode('port')->defaultValue('9876')->end()
                        ->scalarNode('http_host')->defaultValue('localhost')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
