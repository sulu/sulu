<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
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
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')->defaultValue('file_system')->end()
                        ->scalarNode('namespace')->defaultNull()->end()
                        ->append($this->addBasicProviderNode('apc'))
                        ->append($this->addBasicProviderNode('apcu'))
                        ->append($this->addBasicProviderNode('array'))
                        ->append($this->addFileSystemNode())
                        ->append($this->addRedisNode())
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param string $name
     *
     * @return NodeDefinition
     */
    private function addBasicProviderNode($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);

        return $node;
    }

    /**
     * Build file_system node configuration definition.
     *
     * @return NodeDefinition
     */
    private function addFileSystemNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('file_system');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('directory')->defaultValue('%sulu.cache_dir%/websocket')->end()
                ->scalarNode('extension')->defaultNull()->end()
                ->integerNode('umask')->defaultValue(0002)->end()
            ->end();

        return $node;
    }

    /**
     * Build redis node configuration definition.
     *
     * @return NodeDefinition
     */
    private function addRedisNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('redis');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('connection_id')->defaultNull()->end()
                ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                ->scalarNode('port')->defaultValue('6379')->end()
                ->scalarNode('password')->defaultNull()->end()
                ->scalarNode('timeout')->defaultNull()->end()
                ->scalarNode('database')->defaultNull()->end()
            ->end();

        return $node;
    }
}
