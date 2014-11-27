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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluMediaExtension extends Extension
{
    const DEFAULT_FORMAT_NAME = '170x170';
    const DEFAULT_GHOST_SCRIPT_PATH = 'ghostscript';
    const DEFAULT_FORMAT_CACHE_PUBLIC_FOLDER = 'web';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_media.search.default_image_format', $config['search']['default_image_format']);
        $container->setParameter('sulu_media.format_manager.response_headers', $config['format_manager']['response_headers']);
        $container->setParameter('sulu_media.collection.type.default', array(
            'id' => 1
        ));
        $container->setParameter('sulu_media.collection.previews.limit', 3);
        $container->setParameter('sulu_media.collection.previews.format', '150x100');
        $container->setParameter('sulu_media.media.max_file_size', '16MB');
        $container->setParameter('sulu_media.media.blocked_file_types', array('file/exe'));
        $container->setParameter('sulu_media.media.storage.local.path', '%kernel.root_dir%/../uploads/media');
        $container->setParameter('sulu_media.media.storage.local.segments', '10');
        $container->setParameter('sulu_media.image.command.prefix', 'image.converter.prefix.');
        $container->setParameter('sulu_media.format_cache.save_image', 'true');
        $container->setParameter('sulu_media.format_cache.path', '%kernel.root_dir%/../' . $config['format_cache']['public_folder'] . '/uploads/media');
        $container->setParameter('sulu_media.format_cache.segments', '10');
        $container->setParameter('ghost_script.path', $config['ghost_script']['path']);
        $container->setParameter('sulu_media.format_manager.mime_types', array(
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/png',
            'image/bmp',
            'image/svg+xml',
            'image/vnd.adobe.photoshop',
            'application/pdf',
        ));

        $container->setParameter('sulu_media.image.formats', array(
            self::DEFAULT_FORMAT_NAME => array(
                'name' => self::DEFAULT_FORMAT_NAME,
                'commands' => array(
                    array(
                        'action' => 'scale',
                        'parameters' => array(
                            'x' => '170',
                            'y' => '170',
                        )
                    )
                )
            ),
            '50x50' => array(
                'name' => '50x50',
                'commands' => array(
                    array(
                        'action' => 'scale',
                        'parameters' =>array(
                            'x' => '50',
                            'y' => '50',
                        )
                    )
                )
            ),
            '150x100' => array(
                'name' => '150x100',
                'commands' => array(
                    array(
                        'action' => 'scale',
                        'parameters' =>array(
                            'x' => '150',
                            'y' => '100',
                        )
                    )
                )
            ),
        ));
        $container->setParameter('sulu_media.media.types', array(
            array(
                'type' => 'document',
                'mimeTypes' => array('*')
            ),
            array(
                'type' => 'image',
                'mimeTypes' => array('image/jpg', 'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/vnd.adobe.photoshop')
            ),
            array(
                'type' => 'video',
                'mimeTypes' => array('video/mp4')
            ),
            array(
                'type' => 'audio',
                'mimeTypes' => array('audio/mpeg')
            )
        ));

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if (true === $config['search']['enabled']) {
            if (!class_exists('Sulu\Bundle\SearchBundle\SuluSearchBundle')) {
                throw new \InvalidArgumentException(
                    'You have enabled sulu search integration for the SuluMediaBundle, but the SuluSearchBundle must be installed'
                );
            }

            $loader->load('search.xml');
        }
    }
}
