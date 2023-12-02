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

use Sulu\Bundle\WebsiteBundle\Entity\Analytics;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
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
            ->arrayNode('segments')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('switch_url')->defaultValue('/_sulu_segment_switch')->end()
                    ->scalarNode('cookie')->defaultValue('_ss')->end()
                    ->scalarNode('header')->defaultValue('X-Sulu-Segment')->end()
                ->end()
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
                                        @trigger_deprecation('sulu/sulu', '2.2', 'Enabling the "urls" parameter is deprecated.');

                                        return $v;
                                    })
                                ->end()
                            ->end()
                            ->booleanNode('path')
                                ->defaultTrue()
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return false !== $v; })
                                    ->then(function($v) {
                                        @trigger_deprecation('sulu/sulu', '2.3', 'Enabling the "path" parameter is deprecated.');

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

        $this->addObjectsSection($rootNode);
        $this->addWebspaceSection($rootNode);

        return $treeBuilder;
    }

    private function addObjectsSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('analytics')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Analytics::class)->end()
                                ->scalarNode('repository')->defaultValue(AnalyticsRepository::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addWebspaceSection(ArrayNodeDefinition $node): void
    {
        $node
            ->fixXmlConfig('webspace')
            ->children()
                ->arrayNode('webspaces')
                ->useAttributeAsKey('key', false)
                ->requiresAtLeastOneElement()
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('key')->isRequired()->example('blog')->end()
                        ->scalarNode('name')->isRequired()->example('John\'s Blog')->end()
                        ->scalarNode('theme')->defaultValue(null)->end()
                        ->arrayNode('navigation')
                            ->fixXmlConfig('context')
                            ->children()
                                ->arrayNode('contexts')
                                    ->requiresAtLeastOneElement()
                                    ->useAttributeAsKey('key', false)
                                    ->arrayPrototype()
                                       ->children()
                                            ->scalarNode('key')->isRequired()->example('main')->end()
                                            ->arrayNode('meta')
                                                ->fixXmlConfig('title')
                                                ->children()
                                                    ->arrayNode('titles')
                                                        ->useAttributeAsKey('language')
                                                        ->scalarPrototype()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('metadata')->end()
                                        ->end()

                                    ->end()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('security')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('system')->isRequired()->end()
                                    ->booleanNode('permissionCheck')->defaultValue(false)->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('resource_locator')
                            ->isRequired()
                            ->children()
                                ->scalarNode('strategy')->defaultValue('tree_leaf_edit')->end()
                            ->end()
                        ->end()

                        ->arrayNode('default_templates')
                            ->children()
                                ->arrayNode('default_template')
                                    ->useAttributeAsKey('type')
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('value')->isRequired()->example('default')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->fixXmlConfig('localization')
                    ->children()
                        ->arrayNode('localizations')
                            ->info('List of languages enabled in this webspace.')
                            ->beforeNormalization()
                                ->ifTrue(fn ($x) => array_sum(array_column($x, 'default')) > 1)
                                ->thenInvalid('You can not have more than one default localization')
                            ->end()
                            ->useAttributeAsKey('language', false)
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('language')->isRequired()->end()
                                    ->scalarNode('country')->defaultNull()->end()
                                    ->scalarNode('shadow')->defaultNull()->end()
                                    ->booleanNode('default')->defaultValue(false)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->fixXmlConfig('segment')
                    ->children()
                        ->arrayNode('segments')
                            ->useAttributeAsKey('key', false)
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('key')->isRequired()->end()
                                    ->booleanNode('default')->end()
                                ->end()

                                ->fixXmlConfig('title')
                                ->children()
                                    ->arrayNode('titles')
                                        ->useAttributeAsKey('language', false)
                                        ->arrayPrototype()
                                            ->children()
                                                ->scalarNode('language')->isRequired()->end()
                                                ->scalarNode('value')->isRequired()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()

                            ->end()
                        ->end()
                    ->end()

                    ->fixXmlConfig('error_template')
                    ->children()
                        ->arrayNode('error_templates')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('code')->defaultNull()->end()
                                    ->booleanNode('default')->defaultFalse()->end()
                                    ->scalarNode('value')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->fixXmlConfig('excluded_template')
                    ->children()
                        ->arrayNode('excluded_templates')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()

                    ->fixXmlConfig('template')
                    ->children()
                        ->arrayNode('templates')
                            ->useAttributeAsKey('type')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('value')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->fixXmlConfig('portal')
                    ->children()
                        ->arrayNode('portals')
                            ->useAttributeAsKey('key', false)
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('key')->isRequired()->end()
                                ->end()

                                ->fixXmlConfig('environment')
                                ->children()
                                    ->arrayNode('environments')
                                        ->useAttributeAsKey('type', false)
                                        ->arrayPrototype()
                                            ->children()
                                                ->scalarNode('type')->isRequired()->example('prod')->end()
                                            ->end()

                                            ->fixXmlConfig('url')
                                            ->children()
                                                ->arrayNode('urls')
                                                    ->arrayPrototype()
                                                        ->beforeNormalization()
                                                            ->castToArray()
                                                        ->end()
                                                        ->children()
                                                            ->scalarNode('language')->defaultNull()->end()
                                                            ->scalarNode('country')->defaultNull()->end()
                                                            ->scalarNode('redirect')->defaultNull()->end()
                                                            ->scalarNode('value')->isRequired()->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()

                                            ->fixXmlConfig('custom_url')
                                            ->children()
                                                ->arrayNode('custom_urls')
                                                    ->scalarPrototype()
                                                        ->isRequired()
                                                        ->beforeNormalization()
                                                            ->ifTrue(fn (string $value) => !str_contains($value, '*'))
                                                            ->thenInvalid('The custom-url %s has no placeholder')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()

                                        ->end()
                                    ->end()
                                ->end()

                                ->fixXmlConfig('localization')
                                ->children()
                                    ->arrayNode('localizations')
                                        ->info('List of languages enabled in this webspace.')
                                        ->example(['language' => 'de', 'default' => true])
                                        ->beforeNormalization()
                                            ->ifTrue(fn ($x) => array_sum(array_column($x, 'default')) > 1)
                                            ->thenInvalid('You can not have more than one default localization')
                                        ->end()
                                        ->arrayPrototype()
                                            ->children()
                                                ->scalarNode('language')->isRequired()->end()
                                                ->scalarNode('country')->defaultNull()->end()
                                                ->booleanNode('default')->defaultValue(false)->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()

                            ->end()
                        ->end()
                    ->end()

                ->end()
            ->end()
        ;
    }
}
