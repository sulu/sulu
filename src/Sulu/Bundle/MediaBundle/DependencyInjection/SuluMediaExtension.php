<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\DependencyInjection;

use FFMpeg\FFMpeg;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Media\Exception\FileVersionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\FormatOptionsMissingParameterException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
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
                [
                    'indexes' => [
                        'media' => [
                            'name' => 'sulu_media.media',
                            'security_context' => 'sulu.media.collections'
                        ],
                    ],
                ]
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
                        __DIR__ . '/../Resources/config/image-formats.xml',
                        '%kernel.project_dir%/config/image-formats.xml',
                    ],
                    'search' => ['enabled' => $container->hasExtension('massive_search')],
                ]
            );
        }

        if ($container->hasExtension('fos_js_routing')) {
            $container->prependExtensionConfig(
                'fos_js_routing',
                [
                    'routes_to_expose' => [
                        'put_media_format',
                    ],
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

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'media' => [
                            'routes' => [
                                'list' => 'cget_media',
                                'detail' => 'get_media',
                            ],
                        ],
                        'media_formats' => [
                            'routes' => [
                                'list' => 'get_media_formats',
                                'detail' => 'put_media_format',
                            ],
                        ],
                        'collections' => [
                            'routes' => [
                                'list' => 'get_collections',
                                'detail' => 'get_collection',
                            ],
                        ],
                        'formats' => [
                            'routes' => [
                                'list' => 'get_formats',
                                'detail' => 'get_format',
                            ],
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
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // collection-class
        $container->setParameter('sulu.model.collection.class', Collection::class);

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
        $loader->load('command.xml');

        if ('auto' === $config['adapter']) {
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
                    'You have enabled sulu search integration for the SuluMediaBundle, but the SuluSearchBundle must be installed'
                );
            }

            $loader->load('search.xml');
        }

        if (array_key_exists('SuluAudienceTargetingBundle', $bundles)) {
            $loader->load('audience_targeting.xml');
        }

        if (class_exists(FFMpeg::class)) {
            if (!array_key_exists('ffmpeg', $config)) {
                throw new InvalidConfigurationException('The child node "ffmpeg" at path "sulu_media" must be configured.');
            }

            $container->setParameter('sulu_media.ffmpeg.binary', $config['ffmpeg']['ffmpeg_binary']);
            $container->setParameter('sulu_media.ffprobe.binary', $config['ffmpeg']['ffprobe_binary']);
            $container->setParameter('sulu_media.ffmpeg.binary_timeout', $config['ffmpeg']['binary_timeout']);
            $container->setParameter('sulu_media.ffmpeg.threads_count', $config['ffmpeg']['threads_count']);

            $loader->load('ffmpeg.xml');
        }

        $this->configurePersistence($config['objects'], $container);
        $this->configureStorage($config, $container, $loader);
    }

    private function configureStorage(array $config, ContainerBuilder $container, LoaderInterface $loader)
    {
        $storage = $config['storage'];
        $container->setParameter('sulu_media.media.storage', $storage);
        foreach ($config['storages'][$storage] as $key => $value) {
            $container->setParameter('sulu_media.media.storage.' . $storage . '.' . $key, $value);
        }

        $loader->load('services_storage_' . $storage . '.xml');

        $container->setAlias('sulu_media.storage', 'sulu_media.storage.' . $storage)->setPublic(true);
    }
}
