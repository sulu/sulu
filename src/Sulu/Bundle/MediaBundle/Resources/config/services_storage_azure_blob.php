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

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use Sulu\Bundle\MediaBundle\Media\Storage\AzureBlobStorage;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.storage.azure_blob.client', BlobRestProxy::class)
        ->args(['%sulu_media.media.storage.azure_blob.connection_string%'])
        ->factory([BlobRestProxy::class, 'createBlobService']);

    $services->set('sulu_media.storage.azure_blob.adapter', AzureBlobStorageAdapter::class)
        ->args([
            new Reference('sulu_media.storage.azure_blob.client'),
            '%sulu_media.media.storage.azure_blob.container_name%',
            '%sulu_media.media.storage.azure_blob.path_prefix%',
        ]);

    $services->set('sulu_media.storage.azure_blob.filesystem', Filesystem::class)
        ->args([new Reference('sulu_media.storage.azure_blob.adapter')]);

    $services->set('sulu_media.storage.azure_blob', AzureBlobStorage::class)
        ->args([
            new Reference('sulu_media.storage.azure_blob.filesystem'),
            new Reference('sulu_media.storage.azure_blob.client'),
            '%sulu_media.media.storage.azure_blob.container_name%',
            '%sulu_media.media.storage.azure_blob.segments%',
        ]);
};
