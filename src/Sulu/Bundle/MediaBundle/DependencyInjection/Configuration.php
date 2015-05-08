<?php
/*
 * This file is part of the Sulu CMS.
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
        $rootNode = $treeBuilder->root('sulu_media');
        $rootNode->children()
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
            ->arrayNode('format_manager')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('response_headers')
                        ->prototype('scalar')->end()->defaultValue(array(
                            'Expires' => '+1 month',
                            'Pragma' => 'public',
                            'Cache-Control' => 'public'
                        ))
                    ->end()
                    ->arrayNode('default_imagine_options')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('config_paths')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('blocked_file_types')
                        ->prototype('scalar')->end()->defaultValue(array('file/exe'))
                    ->end()
                    ->arrayNode('mime_types')
                        ->prototype('scalar')->end()->defaultValue(array(
                            'image/jpeg',
                            'image/jpg',
                            'image/gif',
                            'image/png',
                            'image/bmp',
                            'image/svg+xml',
                            'image/vnd.adobe.photoshop',
                            'application/pdf',
                        ))
                    ->end()
                    ->arrayNode('types')
                        ->prototype('scalar')->end()->defaultValue(array(
                            array(
                                'type' => 'document',
                                'mimeTypes' => array('*')
                            ),
                            array(
                                'type' => 'image',
                                'mimeTypes' => array('image/*')
                            ),
                            array(
                                'type' => 'video',
                                'mimeTypes' => array('video/*')
                            ),
                            array(
                                'type' => 'audio',
                                'mimeTypes' => array('audio/*')
                            )
                        ))
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
            ->end();

        return $treeBuilder;
    }
}
