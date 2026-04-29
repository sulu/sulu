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

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Filesystem;
use Sulu\Bundle\MediaBundle\Media\Storage\GoogleCloudStorage;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.storage.google_cloud.client', StorageClient::class)
        ->args([['keyFilePath' => '%sulu_media.media.storage.google_cloud.key_file_path%']]);

    $services->set('sulu_media.storage.google_cloud.bucket', Bucket::class)
        ->args(['%sulu_media.media.storage.google_cloud.bucket_name%'])
        ->factory([new Reference('sulu_media.storage.google_cloud.client'), 'bucket']);

    $services->set('sulu_media.storage.google_cloud.adapter', GoogleStorageAdapter::class)
        ->args([
            new Reference('sulu_media.storage.google_cloud.client'),
            new Reference('sulu_media.storage.google_cloud.bucket'),
            '%sulu_media.media.storage.google_cloud.path_prefix%',
        ]);

    $services->set('sulu_media.storage.google_cloud.filesystem', Filesystem::class)
        ->args([new Reference('sulu_media.storage.google_cloud.adapter')]);

    $services->set('sulu_media.storage.google_cloud', GoogleCloudStorage::class)
        ->args([
            new Reference('sulu_media.storage.google_cloud.filesystem'),
            '%sulu_media.media.storage.google_cloud.segments%',
        ]);
};
