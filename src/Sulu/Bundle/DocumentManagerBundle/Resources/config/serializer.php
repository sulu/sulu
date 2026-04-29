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

use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_document_manager.serializer.subscriber.proxy', \Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer\Subscriber\ProxySubscriber::class)
        ->public()
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_document_manager.serializer.subscriber.document', \Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer\Subscriber\DocumentSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_document_manager.node_manager'),
            new Reference('sulu_document_manager.metadata_factory'),
        ])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_document_manager.serializer.subscriber.children_behavior', \Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer\Subscriber\ChildrenSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_registry'),
        ])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_document_manager.serializer.handler.children_collection', \Sulu\Bundle\DocumentManagerBundle\Bridge\Serializer\Handler\ChildrenCollectionHandler::class)
        ->tag('jms_serializer.subscribing_handler');
};
