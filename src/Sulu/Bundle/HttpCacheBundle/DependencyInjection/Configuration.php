<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\DependencyInjection;

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
        $root = $treeBuilder->root('sulu_http_cache');

        $root
            ->children()
                ->arrayNode('handlers')
                    ->addDefaultsIfNotSet()
                    ->info('Configuration for structure cache handlers')
                    ->children()
                        ->arrayNode('public')
                            ->canBeDisabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('max_age')->defaultValue(240)->end()
                                ->integerNode('shared_max_age')->defaultValue(240)->end()
                                ->booleanNode('use_page_ttl')
                                    ->defaultValue(true)
                                    ->info('Use the dynamic pages cache lifetime for reverse proxy server')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('url')
                            ->canBeDisabled()
                        ->end()
                        ->arrayNode('tags')
                            ->canBeEnabled()
                        ->end()
                        ->arrayNode('debug')
                            ->canBeDisabled()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('proxy_client')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('symfony')
                            ->canBeEnabled()
                        ->end()
                        ->arrayNode('varnish')
                            ->canBeEnabled()
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('server')
                            ->children()
                                ->arrayNode('servers')
                                    ->beforeNormalization()->ifString()->then(function ($v) {
                                        return preg_split('/\s*,\s*/', $v);
                                    })->end()
                                    ->useAttributeAsKey('name')
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->prototype('scalar')->end()
                                    ->info('Addresses of the hosts Varnish is running on. May be hostname or ip, and with :port if not the default port 80.')
                                ->end()
                                ->scalarNode('base_url')
                                    ->defaultNull()
                                    ->info('Default host name and optional path for path based invalidation.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
