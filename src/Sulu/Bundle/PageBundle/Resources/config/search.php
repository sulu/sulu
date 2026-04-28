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

use Sulu\Bundle\PageBundle\Search\EventListener\HitListener;
use Sulu\Bundle\PageBundle\Search\EventSubscriber\BlameTimestampSubscriber;
use Sulu\Bundle\PageBundle\Search\EventSubscriber\StructureSubscriber;
use Sulu\Bundle\PageBundle\Search\Reindex\StructureProvider;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_page.search.metadata.provider.structure.class', \Sulu\Bundle\PageBundle\Search\Metadata\StructureProvider::class);
    $parameters->set('sulu_page.search.event_subscriber.blame_timestamp.class', BlameTimestampSubscriber::class);
    $parameters->set('sulu_page.search.event_subscriber.structure.class', StructureSubscriber::class);
    $parameters->set('sulu_search.event_listener.hit.class', HitListener::class);

    $services->set('sulu_page.search.metadata.provider.structure', '%sulu_page.search.metadata.provider.structure.class%')
        ->args([
            new Reference('massive_search.factory'),
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_page.extension.manager'),
            '%sulu_page.search.mapping%',
        ])
        ->tag('massive_search.metadata.provider');

    $services->set('sulu_page.search.event_subscriber.blame_timestamp', '%sulu_page.search.event_subscriber.blame_timestamp.class%')
        ->args([
            new Reference('massive_search.factory'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_page.search.reindex.structure_provider', StructureProvider::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.metadata_factory.base'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.document_inspector'),
            '%sulu.context%',
        ])
        ->tag('massive_search.reindex.provider', ['id' => 'sulu_structure']);

    $services->set('sulu_page.search.event_subscriber.structure', '%sulu_page.search.event_subscriber.structure.class%')
        ->args([new Reference('massive_search.search_manager')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_search.event_listener.hit', '%sulu_search.event_listener.hit.class%')
        ->args([new Reference('sulu_core.webspace.request_analyzer')])
        ->tag('kernel.event_listener', ['event' => 'massive_search.hit', 'method' => 'onHit'])
        ->tag('sulu.context', ['context' => 'website']);
};
