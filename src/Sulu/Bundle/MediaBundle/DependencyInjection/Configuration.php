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

use FFMpeg\FFMpeg;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const STORAGE_LOCAL = 'local';

    const STORAGE_GOOGLE_CLOUD = 'google_cloud';

    const STORAGE_S3 = 's3';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_media');
        $rootNode->children()
            ->scalarNode('adobe_creative_key')->defaultNull()->end()
            ->scalarNode('adapter')
                ->defaultValue('auto')
                ->validate()
                    ->ifTrue(function ($v) {
                        return !in_array($v, ['auto', 'gd', 'imagick', 'gmagick']);
                    })
                    ->thenInvalid('Invalid imagine adapted specified: %s')
                ->end()
            ->end()
            ->arrayNode('image_format_files')
                ->prototype('scalar')->end()
            ->end()
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
                    ->scalarNode('default_image_format')->defaultValue('sulu-170x170')->end()
                    ->booleanNode('enabled')->info(
                        'Enable integration with SuluMediaBundle'
                    )->defaultValue(false)->end()
                ->end()
            ->end()
            ->arrayNode('ghost_script')
                ->addDefaultsIfNotSet()
                ->children()
                     ->scalarNode('path')->defaultValue('gs')->end()
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
                    ->scalarNode('path')->defaultValue('%kernel.project_dir%/public/uploads/media')->end()
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
            ->end()
            ->arrayNode('ffmpeg')
                ->children()
                    ->scalarNode('ffmpeg_binary')
                        ->isRequired()
                    ->end()
                    ->scalarNode('ffprobe_binary')
                        ->isRequired()
                    ->end()
                    ->scalarNode('binary_timeout')->defaultValue(60)->end()
                    ->scalarNode('threads_count')->defaultValue(4)->end()
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);
        $this->addStorageSection($rootNode);

        return $treeBuilder;
    }

    private function addStorageSection(ArrayNodeDefinition $node)
    {
        $storages = [self::STORAGE_LOCAL];
        $storagesNode = $node->children()
            ->arrayNode('storages')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode(self::STORAGE_LOCAL)
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('path')->defaultValue('%kernel.project_dir%/var/uploads/media')->end()
                            ->scalarNode('segments')->defaultValue(10)->end()
                        ->end()
                    ->end();

        if (class_exists(GoogleStorageAdapter::class)) {
            $storages[] = self::STORAGE_GOOGLE_CLOUD;

            $storagesNode
                ->arrayNode(self::STORAGE_GOOGLE_CLOUD)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('key_file_path')->isRequired()->end()
                        ->scalarNode('bucket_name')->isRequired()->end()
                        ->scalarNode('segments')->defaultValue(10)->end()
                    ->end()
                ->end();
        }

        if (class_exists(AwsS3Adapter::class)) {
            $storages[] = self::STORAGE_S3;
            $storagesNode
                ->arrayNode(self::STORAGE_S3)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('key')->isRequired()->end()
                        ->scalarNode('secret')->isRequired()->end()
                        ->scalarNode('region')->isRequired()->end()
                        ->scalarNode('bucket_name')->isRequired()->end()
                        ->scalarNode('version')->defaultValue('latest')->end()
                        ->scalarNode('endpoint')->defaultNull()->end()
                        ->scalarNode('segments')->defaultValue(10)->end()
                        ->arrayNode('arguments')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end();
        }

        $node->children()
                ->enumNode('storage')->values($storages)->defaultValue(self::STORAGE_LOCAL)->end()
            ->end();
    }

    /**
     * Adds `objects` section.
     *
     * @param ArrayNodeDefinition $node
     */
    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('media')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue('Sulu\Bundle\MediaBundle\Entity\Media')
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue('Sulu\Bundle\MediaBundle\Entity\MediaRepository')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
