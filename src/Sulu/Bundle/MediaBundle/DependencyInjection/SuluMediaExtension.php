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
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
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
use Symfony\Component\Process\ExecutableFinder;

class SuluMediaExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * @var ExecutableFinder
     */
    private $executableFinder;

    public function __construct(?ExecutableFinder $executableFinder = null)
    {
        $this->executableFinder = $executableFinder ?: new ExecutableFinder();
    }

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
                            'icon' => 'su-image',
                            'view' => [
                                'name' => MediaAdmin::EDIT_FORM_VIEW,
                                'result_to_view' => [
                                    'id' => 'id',
                                    'locale' => 'locale',
                                ],
                            ],
                            'security_context' => 'sulu.media.collections',
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
                        'sulu_media.put_media_format',
                        'sulu_media.delete_media_version',
                        'sulu_media.post_media_preview',
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
                                'list' => 'sulu_media.cget_media',
                                'detail' => 'sulu_media.get_media',
                            ],
                            'security_context' => 'sulu.media.collections',
                            'security_class' => Collection::class,
                        ],
                        'media_preview' => [
                            'routes' => [
                                'detail' => 'sulu_media.post_media_preview',
                            ],
                        ],
                        'media_formats' => [
                            'routes' => [
                                'list' => 'sulu_media.get_media_formats',
                                'detail' => 'sulu_media.put_media_format',
                            ],
                        ],
                        'media_versions' => [
                            'routes' => [
                                'detail' => 'sulu_media.delete_media_version',
                            ],
                        ],
                        'collections' => [
                            'routes' => [
                                'list' => 'sulu_media.get_collections',
                                'detail' => 'sulu_media.get_collection',
                            ],
                        ],
                        'formats' => [
                            'routes' => [
                                'list' => 'sulu_media.get_formats',
                                'detail' => 'sulu_media.get_format',
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

        // format cache
        $container->setParameter('sulu_media.format_cache.path', $config['format_cache']['path']);
        $container->setParameter('sulu_media.format_cache.save_image', $config['format_cache']['save_image']);
        $container->setParameter('sulu_media.format_cache.segments', $config['format_cache']['segments']);

        // converter
        $ghostScriptPath = $config['ghost_script']['path'];
        $container->setParameter('sulu_media.ghost_script.path', $ghostScriptPath);

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

        $ffmpegBinary = $config['ffmpeg']['ffmpeg_binary'] ?? null;
        $ffprobeBinary = $config['ffmpeg']['ffprobe_binary'] ?? null;

        if (method_exists($container, 'resolveEnvPlaceholders')) {
            $ffmpegBinary = $container->resolveEnvPlaceholders($ffmpegBinary, true);
            $ffprobeBinary = $container->resolveEnvPlaceholders($ffprobeBinary, true);
        }

        if ($ffmpegBinary || $ffprobeBinary) {
            if (!class_exists(FFMpeg::class)) {
                throw new InvalidConfigurationException(
                    'The "php-ffmpeg/php-ffmpeg" need to be installed to use ffmpeg.'
                );
            }

            $container->setParameter('sulu_media.ffmpeg.binary', $config['ffmpeg']['ffmpeg_binary']);
            $container->setParameter('sulu_media.ffprobe.binary', $config['ffmpeg']['ffprobe_binary']);
            $container->setParameter('sulu_media.ffmpeg.binary_timeout', $config['ffmpeg']['binary_timeout']);
            $container->setParameter('sulu_media.ffmpeg.threads_count', $config['ffmpeg']['threads_count']);

            $loader->load('ffmpeg.xml');
        }

        $mimeTypes = $config['format_manager']['mime_types'];
        if (0 === count($mimeTypes)) {
            $mimeTypes = $this->getSupportedMimeTypes($ghostScriptPath, $ffmpegBinary, $ffprobeBinary);
        }
        $container->setParameter('sulu_media.format_manager.mime_types', $mimeTypes);

        $this->configurePersistence($config['objects'], $container);
        $this->configureStorage($config, $container, $loader);
    }

    private function configureStorage(array $config, ContainerBuilder $container, LoaderInterface $loader)
    {
        $storage = $config['storage'];
        if (method_exists($container, 'resolveEnvPlaceholders')) {
            $storage = $container->resolveEnvPlaceholders($storage, true);
        }
        $container->setParameter('sulu_media.media.storage', $storage);

        foreach ($config['storages'] as $storageKey => $storageConfig) {
            foreach ($storageConfig as $key => $value) {
                if ($storageKey === $storage) {
                    $container->setParameter('sulu_media.media.storage.' . $storageKey . '.' . $key, $value);
                } else {
                    if (method_exists($container, 'resolveEnvPlaceholders')) {
                        // Resolve unused ENV Variables of other Adapter
                        $container->resolveEnvPlaceholders($value, true);
                    }
                }
            }
        }

        $loader->load('services_storage_' . $storage . '.xml');

        $container->setAlias('sulu_media.storage', 'sulu_media.storage.' . $storage)->setPublic(true);
    }

    private function getSupportedMimeTypes($ghostScriptPath, $ffmpegBinary, $ffprobeBinary)
    {
        $mimeTypes = ['image/*'];

        if ($ffmpegBinary
            && $ffprobeBinary
            && $this->checkCommandAvailability($ffmpegBinary)
            && $this->checkCommandAvailability($ffprobeBinary)
        ) {
            $mimeTypes[] = 'video/*';
        }

        if ($ghostScriptPath && $this->checkCommandAvailability($ghostScriptPath)) {
            $mimeTypes[] = 'application/pdf';
        }

        return $mimeTypes;
    }

    private function checkCommandAvailability($command)
    {
        return null !== $this->executableFinder->find($command);
    }
}
