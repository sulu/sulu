<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\VideoPropertiesProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $services->set('sulu_media.ffmpeg', FFMpeg::class)
        ->lazy()
        ->args([
            ['ffmpeg.binaries' => '%sulu_media.ffmpeg.binary%', 'ffprobe.binaries' => '%sulu_media.ffprobe.binary%', 'timeout' => '%sulu_media.ffmpeg.binary_timeout%', 'ffmpeg.threads' => '%sulu_media.ffmpeg.threads_count%'],
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->factory([FFMpeg::class, 'create']);

    $services->set('sulu_media.ffprobe', FFProbe::class)
        ->lazy()
        ->args([
            ['ffmpeg.binaries' => '%sulu_media.ffmpeg.binary%', 'ffprobe.binaries' => '%sulu_media.ffprobe.binary%'],
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->factory([FFProbe::class, 'create']);

    $services->set('sulu_media.video_properties_provider', VideoPropertiesProvider::class)
        ->args([new Reference('sulu_media.ffprobe')])
        ->tag('sulu_media.media_properties_provider');
};
