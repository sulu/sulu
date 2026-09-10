<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal is not part of the public API and should only be called by the Symfony framework classes
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * Keys whose plugin needs a block element to carry it, which "enter_mode: br" strips from the stored
     * value. See Resources/js/containers/CKEditor5/utils.js removePTags().
     */
    private const BLOCK_TEXT_EDITOR_KEYS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'table', 'code', 'align',
    ];

    public function __construct(private bool $debug)
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_admin');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('name')->defaultValue('Sulu Admin')->end()
                ->scalarNode('email')->defaultValue('')->end()
                ->scalarNode('user_data_service')->defaultValue('sulu_security.user_manager')->end()
                ->arrayNode('resources')
                    ->useAttributeAsKey('resourceKey')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('routes')
                                ->children()
                                    ->scalarNode('list')->end()
                                    ->scalarNode('detail')->end()
                                ->end()
                            ->end()
                            ->arrayNode('views')
                                ->children()
                                    ->scalarNode('list')->end()
                                    ->scalarNode('detail')->end()
                                ->end()
                            ->end()
                            ->scalarNode('security_context')->end()
                            ->scalarNode('security_class')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('collaboration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultValue(!$this->debug)
                        ->end()
                        ->scalarNode('interval')
                            ->defaultValue(20)
                            ->info('The seconds between the keep alive messages for the collaboration feature')
                        ->end()
                        ->scalarNode('threshold')
                            ->defaultValue(60)
                            ->info('The time after which a collabaration without keep alive signal is terminated')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('forms')
                    ->children()
                        ->arrayNode('directories')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('templates')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('default_type')
                                ->defaultValue(null)
                                ->example('default')
                            ->end()
                            ->arrayNode('directories')
                                ->useAttributeAsKey('name')
                                ->scalarPrototype()
                                    ->example('%kernel.project_dir%/config/templates/pages')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('lists')
                    ->children()
                        ->arrayNode('directories')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('text_editor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('configs')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->validate()
                                ->ifTrue(fn ($configs) => [] !== self::invalidKeys($configs))
                                ->thenInvalid('A text editor config name must be a non-empty string, got %s')
                            ->end()
                            ->prototype('array')
                                ->children()
                                    ->enumNode('enter_mode')
                                        ->values(['p', 'br'])
                                        ->defaultValue('p')
                                        ->info('Whether the editor produces paragraphs or line breaks')
                                    ->end()
                                    ->arrayNode('tags')
                                        ->useAttributeAsKey('name')
                                        ->normalizeKeys(false)
                                        ->prototype('boolean')->end()
                                        ->info('The HTML tags the editor is allowed to produce')
                                    ->end()
                                    ->arrayNode('features')
                                        ->useAttributeAsKey('name')
                                        ->normalizeKeys(false)
                                        ->prototype('boolean')->end()
                                        ->info('Editor capabilities that are not an HTML tag, e.g. "align"')
                                    ->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(fn ($config) => [] !== self::invalidKeys(self::keyMap($config)))
                                    ->thenInvalid('A text editor tag or feature must be a non-empty string, got %s')
                                ->end()
                                ->validate()
                                    ->ifTrue(fn ($config) => 'br' === self::enterMode($config)
                                        && [] !== \array_intersect(self::BLOCK_TEXT_EDITOR_KEYS, self::enabledKeys($config)))
                                    ->thenInvalid(
                                        'A text editor config with "enter_mode: br" cannot enable a key that needs a '
                                        . 'block element, because the paragraphs carrying it are stripped from the '
                                        . 'stored value. Remove the block key or use "enter_mode: p". Got %s'
                                    )
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('icon_sets')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->prototype('scalar')
                        ->validate()
                            ->ifTrue(function($value) {
                                return !\is_string($value) || !\preg_match('/^(icomoon:\/\/|svg:\/\/)/', $value);
                            })
                            ->thenInvalid('The icon set path must start with "icomoon://" or "svg://"')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('field_type_options')
                    ->children()
                        ->arrayNode('selection')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('default_type')
                                        ->isRequired()
                                        ->validate()
                                            ->ifNotInArray(['auto_complete', 'list', 'list_overlay'])
                                            ->thenInvalid('Invalid selection type "%s"')
                                        ->end()
                                    ->end()
                                    ->scalarNode('resource_key')->isRequired()->end()
                                    ->arrayNode('view')
                                        ->children()
                                            ->scalarNode('name')->isRequired()->end()
                                            ->arrayNode('result_to_view')
                                                ->isRequired()
                                                ->prototype('scalar')->end()
                                            ->end()
                                            ->arrayNode('result_to_view_name')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('types')
                                        ->isRequired()
                                        ->children()
                                            ->arrayNode('auto_complete')
                                                ->children()
                                                    ->booleanNode('allow_add')->defaultFalse()->end()
                                                    ->scalarNode('id_property')->defaultValue('id')->end()
                                                    ->scalarNode('display_property')->isRequired()->end()
                                                    ->scalarNode('filter_parameter')->end()
                                                    ->arrayNode('search_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('list')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                    ->scalarNode('list_key')->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('list_overlay')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                    ->scalarNode('list_key')->end()
                                                    ->arrayNode('display_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('icon')->isRequired()->end()
                                                    ->scalarNode('label')->isRequired()->end()
                                                    ->scalarNode('overlay_title')->isRequired()->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('single_selection')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('default_type')
                                        ->isRequired()
                                        ->validate()
                                            ->ifNotInArray(['auto_complete', 'list_overlay', 'single_select'])
                                            ->thenInvalid('Invalid selection type "%s"')
                                        ->end()
                                    ->end()
                                    ->scalarNode('resource_key')->isRequired()->end()
                                    ->arrayNode('view')
                                        ->children()
                                            ->scalarNode('name')->isRequired()->end()
                                            ->arrayNode('result_to_view')
                                                ->isRequired()
                                                ->prototype('scalar')->end()
                                            ->end()
                                            ->arrayNode('result_to_view_name')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('types')
                                        ->isRequired()
                                        ->children()
                                            ->arrayNode('auto_complete')
                                                ->children()
                                                    ->scalarNode('display_property')->isRequired()->end()
                                                    ->arrayNode('search_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('list_overlay')
                                                ->children()
                                                    ->scalarNode('adapter')->isRequired()->end()
                                                    ->arrayNode('detail_options')
                                                        ->normalizeKeys(false)
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('list_key')->end()
                                                    ->arrayNode('display_properties')
                                                        ->isRequired()
                                                        ->requiresAtLeastOneElement()
                                                        ->prototype('scalar')
                                                        ->end()
                                                    ->end()
                                                    ->scalarNode('icon')->isRequired()->end()
                                                    ->scalarNode('empty_text')->isRequired()->end()
                                                    ->scalarNode('overlay_title')->isRequired()->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('single_select')
                                                ->children()
                                                    ->scalarNode('display_property')->isRequired()->end()
                                                    ->scalarNode('id_property')->isRequired()->end()
                                                    ->scalarNode('overlay_title')->isRequired()->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function keyMap(mixed $config): array
    {
        if (!\is_array($config)) {
            return [];
        }

        $tags = \is_array($config['tags'] ?? null) ? $config['tags'] : [];
        $features = \is_array($config['features'] ?? null) ? $config['features'] : [];

        return [...$tags, ...$features];
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function invalidKeys(mixed $map): array
    {
        if (!\is_array($map)) {
            return [];
        }

        return \array_filter(\array_keys($map), fn ($key) => !\is_string($key) || '' === $key);
    }

    private static function enterMode(mixed $config): string
    {
        $enterMode = \is_array($config) ? ($config['enter_mode'] ?? 'p') : 'p';

        return \is_string($enterMode) ? $enterMode : 'p';
    }

    /**
     * @return string[]
     */
    private static function enabledKeys(mixed $config): array
    {
        return \array_map(\strval(...), \array_keys(\array_filter(self::keyMap($config))));
    }
}
