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

use Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.storage.local.file_system', Filesystem::class);

    $services->set('sulu_media.storage.local', LocalStorage::class)
        ->args([
            '%sulu_media.media.storage.local.path%',
            '%sulu_media.media.storage.local.segments%',
            new Reference('sulu_media.storage.local.file_system'),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);
};
