<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Sulu\Bundle\MediaBundle\Media\Storage\S3Storage;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.storage.s3.client', S3Client::class)
        ->args([
            [
                'credentials' => [
                    'key' => '%sulu_media.media.storage.s3.key%',
                    'secret' => '%sulu_media.media.storage.s3.secret%',
                ],
                'region' => '%sulu_media.media.storage.s3.region%',
                'version' => '%sulu_media.media.storage.s3.version%',
                'endpoint' => '%sulu_media.media.storage.s3.endpoint%',
            ],
        ]);

    $services->set('sulu_media.storage.s3.adapter', AwsS3Adapter::class)
        ->args([
            new Reference('sulu_media.storage.s3.client'),
            '%sulu_media.media.storage.s3.bucket_name%',
            '%sulu_media.media.storage.s3.path_prefix%',
        ]);

    $services->set('sulu_media.storage.s3.filesystem', Filesystem::class)
        ->args([new Reference('sulu_media.storage.s3.adapter')]);

    $services->set('sulu_media.storage.s3', S3Storage::class)
        ->args([
            new Reference('sulu_media.storage.s3.filesystem'),
            '%sulu_media.media.storage.s3.segments%',
            '%sulu_media.media.storage.s3.public_url%',
        ]);
};
