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

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('sulu_tag.tag_trash_item_handler', \Sulu\Bundle\TagBundle\Trash\TagTrashItemHandler::class)
        ->args([
            service('sulu_trash.trash_item_repository'),
            service('sulu.repository.tag'),
            service('sulu_trash.doctrine_restore_helper'),
            service('doctrine.orm.entity_manager'),
            service('sulu_activity.domain_event_collector'),
        ])
        ->tag('sulu_trash.store_trash_item_handler')
        ->tag('sulu_trash.restore_trash_item_handler')
        ->tag('sulu_trash.restore_configuration_provider');
};
