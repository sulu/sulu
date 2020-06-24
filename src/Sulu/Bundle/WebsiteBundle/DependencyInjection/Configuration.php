<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sulu_website');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('analytics')
                ->canBeDisabled()
            ->end()
            ->arrayNode('twig')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('attributes')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('urls')
                                ->defaultTrue()
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return false !== $v; })
                                    ->then(function($v) {
                                        @\trigger_error('Enable the urls parameter is deprecated since sulu/sulu 2.2.', \E_USER_DEPRECATED);

                                        return $v;
                                    })
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('navigation')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache_lifetime')->defaultValue(1)->end()
                        ->end()
                    ->end()
                    ->arrayNode('content')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache_lifetime')->defaultValue(1)->end()
                        ->end()
                    ->end()
                    ->arrayNode('sitemap')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache_lifetime')->defaultValue(43200)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('sitemap')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('dump_dir')->defaultValue('%sulu.cache_dir%/sitemaps')->end()
                ->end()
            ->end()
            ->arrayNode('default_locale')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('provider_service_id')->defaultValue('sulu_website.default_locale.portal_provider')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
