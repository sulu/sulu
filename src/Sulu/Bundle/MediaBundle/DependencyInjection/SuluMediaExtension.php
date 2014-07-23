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

        $container->setParameter('sulu_media.collection.type.default', array(
            'id' => 1
        ));
        $container->setParameter('sulu_media.collection.previews.limit', 3);
        $container->setParameter('sulu_media.collection.previews.format', '150x100');
        $container->setParameter('sulu_media.media.max_file_size', '16MB');
        $container->setParameter('sulu_media.media.blocked_file_types', array('file/exe'));
        $container->setParameter('sulu_media.media.storage.local.path', '%kernel.root_dir%/../../uploads/media');
        $container->setParameter('sulu_media.media.storage.local.segments', '10');
        $container->setParameter('sulu_media.image.command.prefix', 'image.converter.prefix.');
        $container->setParameter('sulu_media.format_cache.save_image', 'true');
        $container->setParameter('sulu_media.format_cache.path', '%kernel.root_dir%/../../web/uploads/media');
        $container->setParameter('sulu_media.format_cache.segments', '10');
        $container->setParameter('ghost_script.path', '/usr/local/bin/gs');
        $container->setParameter('sulu_media.format_manager.extensions', array(
            'jpeg',
            'jpg',
            'gif',
            'png',
            'bmp',
            'svg',
            'psd',
            'pdf',
        ));
        $container->setParameter('sulu_media.image.formats', array(
            array(
                'name' => '170x170',
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
            array(
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
            array(
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
                'id' => 1,
                'type' => 'default',
                'extensions' => array('*')
            ),
            array(
                'id' => 2,
                'type' => 'image',
                'extensions' => array('jpg', 'jpeg', 'png', 'gif', 'svg')
            ),
            array(
                'id' => 3,
                'type' => 'video',
                'extensions' => array('mp4')
            ),
            array(
                'id' => 4,
                'type' => 'audio',
                'extensions' => array('mp3')
            )
        ));

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
