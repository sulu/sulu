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

use Sulu\Bundle\RouteBundle\Document\Subscriber\PageTreeRouteSubscriber;
use Sulu\Bundle\RouteBundle\PageTree\PageTreeRepository;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_route.subscriber.page_tree_route', PageTreeRouteSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_route.page_tree_route.updater'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_route.page_tree_route.updater.request', PageTreeRepository::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
        ]);
};
