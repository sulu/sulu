<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_media');
        $rootNode->children()
            ->arrayNode('system_collections')
                ->useAttributeAsKey('key')
                ->prototype('array')
                    ->children()
                        ->arrayNode('meta_title')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('collections')
                            ->useAttributeAsKey('key')
                                ->prototype('array')
                                ->children()
                                    ->arrayNode('meta_title')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('search')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('default_image_format')->defaultValue('170x170')->end()
                    ->booleanNode('enabled')->info('Enable integration with SuluMediaBundle')->defaultValue(false)->end()
                ->end()
            ->end()
            ->arrayNode('ghost_script')
                ->addDefaultsIfNotSet()
                ->children()
                     ->scalarNode('path')->defaultValue('gs')->end()
                ->end()
            ->end()
            ->arrayNode('storage')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('local')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('path')->defaultValue('%kernel.root_dir%/../uploads/media')->end()
                            ->scalarNode('segments')->defaultValue(10)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('upload')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('max_filesize')
                        ->defaultValue(256)
                        ->min(0)
                    ->end()
                ->end()
            ->end()
            ->arrayNode('format_manager')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('response_headers')
                        ->prototype('scalar')->end()->defaultValue([
                            'Expires' => '+1 month',
                            'Pragma' => 'public',
                            'Cache-Control' => 'public',
                        ])
                    ->end()
                    ->arrayNode('default_imagine_options')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('config_paths')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('blocked_file_types')
                        ->prototype('scalar')->end()->defaultValue(['file/exe'])
                    ->end()
                    ->arrayNode('mime_types')
                        ->prototype('scalar')->end()->defaultValue([
                            'image/*',
                            'video/*',
                            'application/pdf',
                        ])
                    ->end()
                    ->arrayNode('types')
                        ->prototype('scalar')->end()->defaultValue([
                            [
                                'type' => 'document',
                                'mimeTypes' => ['*'],
                            ],
                            [
                                'type' => 'image',
                                'mimeTypes' => ['image/*'],
                            ],
                            [
                                'type' => 'video',
                                'mimeTypes' => ['video/*'],
                            ],
                            [
                                'type' => 'audio',
                                'mimeTypes' => ['audio/*'],
                            ],
                        ])
                    ->end()
                ->end()
            ->end()
            ->arrayNode('format_cache')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('path')->defaultValue('%kernel.root_dir%/../web/uploads/media')->end()
                    ->booleanNode('save_image')->defaultValue(true)->end()
                    ->scalarNode('segments')->defaultValue(10)->end()
                ->end()
            ->end()
            ->arrayNode('disposition_type')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('default')->values(['inline', 'attachment'])->defaultValue('attachment')->end()
                    ->arrayNode('mime_types_inline')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('mime_types_attachment')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('routing')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('media_proxy_path')->defaultValue('/uploads/media/{slug}')->end()
                    ->scalarNode('media_download_path')->defaultValue('/media/{id}/download/{slug}')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
