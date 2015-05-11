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
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // format manager
        $container->setParameter('sulu_media.format_manager.response_headers', $config['format_manager']['response_headers']);
        $container->setParameter(
            'sulu_media.format_manager.default_imagine_options',
            $config['format_manager']['default_imagine_options']
        );
        $container->setParameter('sulu_media.format_manager.config_paths', $config['format_manager']['config_paths']);
        $container->setParameter('sulu_media.format_manager.mime_types', $config['format_manager']['mime_types']);

        // format cache
        $container->setParameter('sulu_media.format_cache.path', $config['format_cache']['path']);
        $container->setParameter('sulu_media.format_cache.save_image', $config['format_cache']['save_image']);
        $container->setParameter('sulu_media.format_cache.segments', $config['format_cache']['segments']);

        // converter
        $container->setParameter('sulu_media.image.command.prefix', 'image.converter.prefix.');
        $container->setParameter('sulu_media.ghost_script.path', $config['ghost_script']['path']);

        // storage
        $container->setParameter('sulu_media.media.max_file_size', '16MB');
        $container->setParameter('sulu_media.media.blocked_file_types', $config['format_manager']['blocked_file_types']);

        // local storage
        $container->setParameter('sulu_media.media.storage.local.path', $config['storage']['local']['path']);
        $container->setParameter('sulu_media.media.storage.local.segments', $config['storage']['local']['segments']);

        // collections
        $container->setParameter('sulu_media.collection.type.default', array(
            'id' => 1
        ));
        $container->setParameter('sulu_media.collection.previews.format', '50x50');

        // media
        $container->setParameter('sulu_media.media.types', $config['format_manager']['types']);

        // search
        $container->setParameter('sulu_media.search.default_image_format', $config['search']['default_image_format']);

        // load services
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        // enable search
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
