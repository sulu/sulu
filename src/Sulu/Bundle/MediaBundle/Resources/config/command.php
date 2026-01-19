<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\MediaBundle\Command\ClearCacheCommand;
use Sulu\Bundle\MediaBundle\Command\InitCommand;
use Sulu\Bundle\MediaBundle\Command\MediaTypeUpdateCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.command.init', InitCommand::class)
        ->args([
            new Reference('filesystem'),
            '%sulu_media.format_cache.path%',
        ])
        ->tag('console.command');

    $services->set('sulu_media.command.clear_cache', ClearCacheCommand::class)
        ->args([new Reference('sulu_media.format_cache_clearer')])
        ->tag('console.command');

    $services->set('sulu_media.command.type_update', MediaTypeUpdateCommand::class)
        ->args([
            new Reference('sulu_media.type_manager'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('console.command');
};
