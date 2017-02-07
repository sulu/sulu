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

use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatOptionsMissingParameterException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SuluMediaExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_search',
                ['indexes' => ['media' => ['security_context' => 'sulu.media.collections']]]
            );
        }

        if ($container->hasExtension('sulu_media')) {
            $container->prependExtensionConfig(
                'sulu_media',
                [
                    'system_collections' => [
                        'sulu_media' => [
                            'meta_title' => ['en' => 'Sulu media', 'de' => 'Sulu Medien'],
                            'collections' => [
                                'preview_image' => [
                                    'meta_title' => ['en' => 'Preview images', 'de' => 'Vorschaubilder'],
                                ],
                            ],
                        ],
                    ],
                    'image_format_files' => [
                        '%kernel.root_dir%/config/image-formats.xml',
                        __DIR__ . '/../Resources/config/image-formats.xml',
                    ],
                    'search' => ['enabled' => $container->hasExtension('massive_search')],
                ]
            );
        }

        if ($container->hasExtension('fos_rest')) {
            $container->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            MediaNotFoundException::class => 404,
                            FileVersionNotFoundException::class => 404,
                            FormatNotFoundException::class => 404,
                            FormatOptionsMissingParameterException::class => 400,
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // image-formats
        $container->setParameter('sulu_media.image_format_files', $config['image_format_files']);

        // system collections
        $container->setParameter('sulu_media.system_collections', $config['system_collections']);

        // routing paths
        $container->setParameter('sulu_media.format_cache.media_proxy_path', $config['routing']['media_proxy_path']);
        $container->setParameter(
            'sulu_media.media_manager.media_download_path',
            $config['routing']['media_download_path']
        );

        // format manager
        $container->setParameter(
            'sulu_media.format_manager.response_headers',
            $config['format_manager']['response_headers']
        );
        $container->setParameter(
            'sulu_media.format_manager.default_imagine_options',
            $config['format_manager']['default_imagine_options']
        );
        $container->setParameter('sulu_media.format_manager.mime_types', $config['format_manager']['mime_types']);

        // format cache
        $container->setParameter('sulu_media.format_cache.path', $config['format_cache']['path']);
        $container->setParameter('sulu_media.format_cache.save_image', $config['format_cache']['save_image']);
        $container->setParameter('sulu_media.format_cache.segments', $config['format_cache']['segments']);

        // converter
        $container->setParameter('sulu_media.ghost_script.path', $config['ghost_script']['path']);

        // storage
        $container->setParameter('sulu_media.media.max_file_size', '16MB');
        $container->setParameter(
            'sulu_media.media.blocked_file_types',
            $config['format_manager']['blocked_file_types']
        );

        // local storage
        $container->setParameter('sulu_media.media.storage.local.path', $config['storage']['local']['path']);
        $container->setParameter('sulu_media.media.storage.local.segments', $config['storage']['local']['segments']);

        // collections
        $container->setParameter('sulu_media.collection.type.default', ['id' => 1]);

        $container->setParameter('sulu_media.collection.previews.format', 'sulu-50x50');

        // media
        $container->setParameter('sulu_media.media.types', $config['format_manager']['types']);

        // search
        $container->setParameter('sulu_media.search.default_image_format', $config['search']['default_image_format']);

        // disposition type
        $container->setParameter('sulu_media.disposition_type.default', $config['disposition_type']['default']);
        $container->setParameter(
            'sulu_media.disposition_type.mime_types_inline',
            $config['disposition_type']['mime_types_inline']
        );
        $container->setParameter(
            'sulu_media.disposition_type.mime_types_attachment',
            $config['disposition_type']['mime_types_attachment']
        );

        // dropzone
        $container->setParameter(
            'sulu_media.upload.max_filesize',
            $config['upload']['max_filesize']
        );

        // Adobe creative sdk
        $container->setParameter(
            'sulu_media.adobe_creative_key',
            $config['adobe_creative_key']
        );

        // load services
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        if ($config['adapter'] === 'auto') {
            $container->setAlias(
                'sulu_media.adapter',
                'sulu_media.adapter.' . (class_exists('Imagick') ? 'imagick' : 'gd')
            );
        } else {
            // set used adapter for imagine
            $container->setAlias('sulu_media.adapter', 'sulu_media.adapter.' . $config['adapter']);
        }

        // enable search
        if (true === $config['search']['enabled']) {
            if (!class_exists('Sulu\Bundle\SearchBundle\SuluSearchBundle')) {
                throw new \InvalidArgumentException(
                    'You have enabled sulu search integration for the SuluMediaBundle, ' .
                    'but the SuluSearchBundle must be installed'
                );
            }

            $loader->load('search.xml');
        }

        $this->configurePersistence($config['objects'], $container);
    }
}
