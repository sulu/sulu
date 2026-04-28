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

use Sulu\Bundle\RouteBundle\Content\Type\PageTreeRouteContentType;
use Sulu\Bundle\RouteBundle\Content\Type\RouteContentType;
use Sulu\Bundle\RouteBundle\Controller\RouteController;
use Sulu\Bundle\RouteBundle\Document\Subscriber\RoutableSubscriber;
use Sulu\Bundle\RouteBundle\PageTree\NullPageTreeUpdater;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_route.content_type', RouteContentType::class)
        ->tag('sulu.content.type', ['alias' => 'route'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_route.content_type.page_tree_route', PageTreeRouteContentType::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_route.chain_generator'),
            new Reference('sulu_route.manager.conflict_resolver.auto_increment'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('sulu.content.type', ['alias' => 'page_tree_route'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'page_tree_route']);

    $services->set('sulu_route.subscriber.routable', RoutableSubscriber::class)
        ->args([
            new Reference('sulu_route.chain_generator'),
            new Reference('sulu_route.manager.route_manager'),
            new Reference('sulu.repository.route'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_route.manager.conflict_resolver.auto_increment'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_route.page_tree_route.updater.off', NullPageTreeUpdater::class);

    $services->set('sulu_route.route_controller', RouteController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu.repository.route'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_route.generator.route_generator'),
            '%sulu_route.resource_key_mappings%',
            new Reference('sulu_route.manager.conflict_resolver.auto_increment'),
            new Reference('sulu_activity.domain_event_collector'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
