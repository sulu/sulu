<?php

declare(strict_types=1);

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class WebspaceConfiguration
{
    public static function addWebspaceSection(ArrayNodeDefinition $node): void
    {
        $webspaceNode = $node
            ->fixXmlConfig('webspace')
            ->children()
                ->arrayNode('webspaces')
                ->useAttributeAsKey('key', false)
                ->requiresAtLeastOneElement()
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('key')->isRequired()->example('blog')->end()
                        ->scalarNode('name')->isRequired()->example('John\'s Blog')->end()
                        ->scalarNode('theme')->defaultValue(null)->end();

        self::addNavigationContext($webspaceNode);
        self::addResourceLocator($webspaceNode);
        self::addSecurity($webspaceNode);
        self::addTemplates($webspaceNode);
        self::addLocalizations($webspaceNode);
        self::addSegments($webspaceNode);
        self::addPortals($webspaceNode);

        $webspaceNode->end()->end()->end();
    }

    private static function addNavigationContext(NodeBuilder $node) :void {
        $node
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
            ->end();
    }

    private static function addResourceLocator(NodeBuilder $node): void
    {
        $node
            ->arrayNode('resource_locator')
                ->addDefaultsIfNotSet()
                ->info('Configuration of the resource locator')
                ->example(['strategy' => 'tree_leaf_edit'])
                ->children()
                    ->enumNode('strategy')
                        ->defaultValue('tree_leaf_edit')
                        ->values(['tree_leaf_edit', 'tree_full_edit'])
                    ->end()
                ->end()
            ->end();
    }

    private static function addSecurity(NodeBuilder $node): void
    {
        $node
            ->arrayNode('security')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('system')->isRequired()->end()
                        ->booleanNode('permissionCheck')->defaultValue(false)->end()
                    ->end()
                ->end()
            ->end();
    }

    private static function addTemplates(NodeBuilder $node): void
    {
        $node->arrayNode('templates')
            ->children()
                ->arrayNode('template')
                    ->useAttributeAsKey('type')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('value')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();


        $node->arrayNode('default_templates')
            ->isRequired()
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
        ->end();

        $node
            ->arrayNode('excluded_templates')
            ->info('This node defines which of the templates should be excluded in the template dropdown of the page form.')
            ->children()
                ->arrayNode('excluded_template')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        $node
            ->arrayNode('error_templates')
            ->info('A list of error templates, which either have a code (for specific status codes) or are general error templates.')
            ->children()
                ->arrayNode('error_template')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('code')->defaultNull()->end()
                            ->booleanNode('default')->defaultFalse()->end()
                            ->scalarNode('value')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private static function addLocalizations(NodeBuilder $node): void
    {
        $node
            ->arrayNode('localizations')
            ->isRequired()
            ->children()
                ->arrayNode('localization')
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
            ->end();
    }

    private static function addSegments(NodeBuilder $node): void
    {
        $node
            ->arrayNode('segments')
            ->children()
                ->arrayNode('segment')
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
            ->end();
    }

    private static function addPortals(NodeBuilder $node): void
    {
    $node
        ->arrayNode('portals')
        ->isRequired()
        ->children()
            ->arrayNode('portal')
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
            ->end();
    }
}
