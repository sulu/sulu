<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_content');

        // add config preview interval
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('preview')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('error_template')
                            ->example('ClientWebsiteBundle:Preview:error.html.twig')
                        ->end()
                        ->arrayNode('fallback')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('interval')
                                    ->defaultValue('5000')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('websocket')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('port')
                                    ->defaultValue('9876')
                                ->end()
                                ->scalarNode('url')
                                    ->defaultValue('ws://sulu.lo')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('types')
                    ->addDefaultsIfNotSet()
                    ->children()
                    ->arrayNode('smart_content')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('template')
                                ->defaultValue('SuluContentBundle:Template:content-types/smart_content.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('internal_links')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('template')
                                ->defaultValue('SuluContentBundle:Template:content-types/internal_links.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('single_internal_link')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('template')
                                ->defaultValue('SuluContentBundle:Template:content-types/single_internal_link.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('phone')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/phone.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('password')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/password.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('url')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/url.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('email')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/email.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('date')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/date.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('time')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/time.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('color')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('template')
                                    ->defaultValue('SuluContentBundle:Template:content-types/color.html.twig')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('checkbox')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('template')
                                ->defaultValue('SuluContentBundle:Template:content-types/checkbox.html.twig')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
