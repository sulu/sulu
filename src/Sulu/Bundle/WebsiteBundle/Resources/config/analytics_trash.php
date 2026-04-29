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

use Sulu\Bundle\WebsiteBundle\Trash\AnalyticsTrashItemHandler;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_website.analytics_trash_item_handler', AnalyticsTrashItemHandler::class)
        ->args([
            new Reference('sulu_trash.trash_item_repository'),
            new Reference('sulu.repository.analytics'),
            new Reference('sulu_website.domains.repository'),
            new Reference('sulu_trash.doctrine_restore_helper'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
            '%kernel.environment%',
        ])
        ->tag('sulu_trash.store_trash_item_handler')
        ->tag('sulu_trash.restore_trash_item_handler')
        ->tag('sulu_trash.restore_configuration_provider');
};
