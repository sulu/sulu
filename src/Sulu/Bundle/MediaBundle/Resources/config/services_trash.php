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

use Sulu\Bundle\MediaBundle\Trash\CollectionTrashItemHandler;
use Sulu\Bundle\MediaBundle\Trash\MediaTrashItemHandler;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_media.media_trash_item_handler', MediaTrashItemHandler::class)
        ->args([
            new Reference('sulu_trash.trash_item_repository'),
            new Reference('sulu.repository.media'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_trash.doctrine_restore_helper'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu_media.storage'),
        ])
        ->tag('sulu_trash.store_trash_item_handler')
        ->tag('sulu_trash.restore_trash_item_handler')
        ->tag('sulu_trash.remove_trash_item_handler')
        ->tag('sulu_trash.restore_configuration_provider');

    $services->set('sulu_media.collection_trash_item_handler', CollectionTrashItemHandler::class)
        ->args([
            new Reference('sulu_trash.trash_item_repository'),
            new Reference('sulu_media.collection_repository'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_trash.doctrine_restore_helper'),
            new Reference('sulu_activity.domain_event_collector'),
        ])
        ->tag('sulu_trash.store_trash_item_handler')
        ->tag('sulu_trash.restore_trash_item_handler')
        ->tag('sulu_trash.restore_configuration_provider');
};
