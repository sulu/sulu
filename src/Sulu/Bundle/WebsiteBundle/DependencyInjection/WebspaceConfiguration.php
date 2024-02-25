<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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

    private static function addNavigationContext(NodeBuilder $node): void
    {
        $node
            ->arrayNode('navigation')
                ->children()
                    ->arrayNode('contexts')
                    ->children()
                        ->arrayNode('context')
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('key', false)
                            ->beforeNormalization()
                                ->always(function($value) {
                                    if (!\array_key_exists(0, $value)) {
                                        return [$value];
                                    }

                                    return $value;
                                })
                            ->end()
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('key')->isRequired()->example('main')->end()
                                    ->arrayNode('meta')
                                        ->children()
                                            ->arrayNode('title')
                                                ->beforeNormalization()
                                                    ->always(function($value) {
                                                        if (!\array_key_exists(0, $value)) {
                                                            return [$value];
                                                        }

                                                        return $value;
                                                    })
                                                ->end()
                                                ->useAttributeAsKey('lang')
                                                ->arrayPrototype()
                                                    ->children()
                                                        ->scalarNode('lang')->isRequired()->end()
                                                        ->scalarNode('value')->isRequired()->end()
                                                    ->end()
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
                        ->values(['tree_leaf_edit', 'tree_full_edit', 'short'])
                    ->end()
                ->end()
            ->end();
    }

    private static function addSecurity(NodeBuilder $node): void
    {
        $node
            ->arrayNode('security')
                ->children()
                    ->scalarNode('system')->isRequired()->end()
                    ->booleanNode('permission_check')->defaultValue(false)->end()
                ->end()
            ->end();
    }

    private static function addTemplates(NodeBuilder $node): void
    {
        $node->arrayNode('templates')
            ->children()
                ->arrayNode('template')
                    ->beforeNormalization()->always(function($value) {
                        if (!\array_key_exists(0, $value)) {
                            return [$value];
                        }

                        return $value;
                    })
                    ->end()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('value')->isRequired()->end()
                            ->scalarNode('type')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $node->arrayNode('default_templates')
            ->isRequired()
            ->children()
                ->arrayNode('default_template')
                    ->beforeNormalization()
                        ->ifTrue(function($value) {
                            $templates = \array_column($value, 'type');
                            if (!\in_array('home', $templates) && !\in_array('homepage', $templates)) {
                                return false;
                            }
                            if (!\in_array('page', $templates)) {
                                return false;
                            }
                        })
                        ->thenInvalid('Expected default templates "page" and "home"')
                    ->end()
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
                    ->beforeNormalization()
                        ->castToArray()
                    ->end()
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
                        ->always(function($value) {
                            if (!\array_key_exists(0, $value)) {
                                return [$value];
                            }
                            if (\array_sum(\array_column($value, 'default')) > 1) {
                                throw new \InvalidArgumentException('You can not have more than one default localization');
                            }

                            return $value;
                        })
                    ->end()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('language')->isRequired()->end()
                            ->scalarNode('country')->defaultValue('')->end()
                            ->scalarNode('shadow')->defaultValue('')->end()
                            ->booleanNode('default')->defaultValue(false)->end()
                            ->arrayNode('localization')
                                ->beforeNormalization()
                                    ->always(function($value) {
                                        if (!\array_key_exists(0, $value)) {
                                            return [$value];
                                        }
                                        if (\array_sum(\array_column($value, 'default')) > 1) {
                                            throw new \InvalidArgumentException('You can not have more than one default localization');
                                        }

                                        return $value;
                                    })
                                ->end()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('language')->isRequired()->end()
                                        ->scalarNode('country')->defaultValue('')->end()
                                        ->scalarNode('shadow')->defaultNull('')->end()
                                        ->booleanNode('default')->defaultValue(false)->end()
                                    ->end()
                                ->end()
                            ->end()
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
                    ->beforeNormalization()
                        ->ifTrue(fn ($value) => 1 !== \array_sum(\array_column($value, 'default')))
                        ->thenInvalid('No default segment in one of the webspaces')
                    ->end()
                    ->useAttributeAsKey('key', false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('key')->isRequired()->end()
                            ->booleanNode('default')->end()
                            ->arrayNode('meta')
                                ->children()
                                    ->arrayNode('title')
                                        ->useAttributeAsKey('lang')
                                        ->arrayPrototype()
                                            ->children()
                                                ->scalarNode('value')->isRequired()->end()
                                            ->end()
                                        ->end()
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
        $environments = $node
            ->arrayNode('portals')
            ->isRequired()
            ->children()
                ->arrayNode('portal')
                    ->useAttributeAsKey('key', false)
                    ->beforeNormalization()
                        ->always(function($value) {
                            if (\array_key_exists('key', $value)) {
                                return [$value];
                            }

                            return $value;
                        })
                    ->end()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('key')->isRequired()->end()

                            ->arrayNode('localizations')
                                ->children()
                                    ->arrayNode('localization')
                                        ->info('List of languages for the portal')
                                        ->example([['language' => 'de', 'default' => true]])
                                        ->beforeNormalization()
                                            ->always(function($value) {
                                                if (\is_string($value) || !\array_key_exists(0, $value)) {
                                                    $value = [$value];
                                                }

                                                if (\array_sum(\array_column($value, 'default')) > 1) {
                                                    throw new \InvalidArgumentException('You can not have more than one default localization');
                                                }

                                                return $value;
                                            })
                                        ->end()
                                        ->arrayPrototype()
                                            ->beforeNormalization()
                                                ->ifString()->then(fn ($value) => ['language' => $value])
                                            ->end()
                                            ->children()
                                                ->scalarNode('language')->isRequired()->end()
                                                ->scalarNode('country')->defaultValue('')->end()
                                                ->booleanNode('default')->defaultValue(false)->end()
                                                ->scalarNode('shadow')->defaultNull()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()

                            ->arrayNode('environments')
                                ->children()
                                    ->arrayNode('environment')
                                    ->beforeNormalization()
                                        ->always(function($value) {
                                            if (!\array_key_exists(0, $value)) {
                                                return [$value];
                                            }

                                            return $value;
                                        })
                                    ->end()
                                    ->useAttributeAsKey('type', false)
                                    ->arrayPrototype()
                                        ->children();

        self::addEnvironments($environments);

        $environments
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private static function addEnvironments(NodeBuilder $environment): void
    {
        $environment
            ->scalarNode('type')->isRequired()->example('prod')->end()
            ->arrayNode('urls')
                ->children()
                    ->arrayNode('url')
                        ->beforeNormalization()
                            ->always(static function($value) {
                                if (\is_string($value) || !\array_key_exists(0, $value)) {
                                    return [$value];
                                }

                                return $value;
                            })
                        ->end()
                        ->arrayPrototype()
                            ->beforeNormalization()
                                ->ifString()->then(fn ($value) => ['value' => $value])
                            ->end()
                            ->children()
                                ->scalarNode('language')->defaultValue('')->end()
                                ->scalarNode('country')->defaultValue('')->end()
                                ->scalarNode('redirect')->defaultValue('')->end()
                                ->scalarNode('value')->isRequired()->end()
                                ->booleanNode('main')->defaultFalse()->end()
                                ->scalarNode('segment')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('custom_urls')
                ->children()
                    ->arrayNode('custom_url')
                        ->beforeNormalization()
                            ->ifString()->then(fn ($value) => [$value])
                        ->end()
                        ->scalarPrototype()
                            ->isRequired()
                            ->beforeNormalization()
                                ->ifTrue(fn (string $value) => !\str_contains($value, '*'))
                                ->thenInvalid('The custom-url %s has no placeholder')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
