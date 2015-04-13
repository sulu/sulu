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
                    ->arrayNode('config_files')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('format_cache')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('public_folder')->defaultValue(SuluMediaExtension::DEFAULT_FORMAT_CACHE_PUBLIC_FOLDER)->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
