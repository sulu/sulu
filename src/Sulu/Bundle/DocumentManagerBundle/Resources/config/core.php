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

use ProxyManager\Factory\LazyLoadingGhostFactory;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollector;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorSubscriber;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Bundle\DocumentManagerBundle\Initializer\RootPathPurgeInitializer;
use Sulu\Bundle\DocumentManagerBundle\Initializer\WorkspaceInitializer;
use Sulu\Bundle\DocumentManagerBundle\Session\SessionManager;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\EventDispatcher\DebugEventDispatcher;
use Sulu\Component\DocumentManager\Metadata\BaseMetadataFactory;
use Sulu\Component\DocumentManager\Metadata\MetadataFactory;
use Sulu\Component\DocumentManager\NameResolver;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\NodeHelper;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\Slugifier\NodeNameSlugifier;
use Sulu\Component\DocumentManager\Slugifier\PathCleanupSlugifier;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\MixinSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Core\InstantiatorSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Core\MappingSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Core\RegistratorSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\FindSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\GeneralSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\QuerySubscriber;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\RefreshSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\RemoveSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\ReorderSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_document_manager.event_dispatcher.debug', DebugEventDispatcher::class)
        ->private()
        ->args([
            new Reference('debug.stopwatch'),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('monolog.logger', ['channel' => 'sulu_document_manager']);

    $services->set('sulu_document_manager.event_dispatcher.standard', EventDispatcher::class)
        ->private();

    $services->set('sulu_document_manager.document_manager', DocumentManager::class)
        ->public()
        ->args([
            new Reference('sulu_document_manager.event_dispatcher'),
            new Reference('sulu_document_manager.node_manager'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_document_manager.document_registry', DocumentRegistry::class)
        ->private()
        ->args(['%sulu.content.language.default%']);

    $services->set('sulu_document_manager.node_manager', NodeManager::class)
        ->private()
        ->args([new Reference('sulu_document_manager.default_session')]);

    $services->set('sulu_document_manager.node_helper', NodeHelper::class);

    $services->set('sulu_document_manager.session_manager', SessionManager::class)
        ->args([
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_document_manager.metadata_factory.base', BaseMetadataFactory::class)
        ->public()
        ->args([
            new Reference('sulu_document_manager.event_dispatcher'),
            '%sulu_document_manager.mapping%',
        ]);

    $services->set('sulu_document_manager.metadata_factory', MetadataFactory::class)
        ->private()
        ->args([new Reference('sulu_document_manager.metadata_factory.base')]);

    $services->set('sulu_document_manager.slugifier', PathCleanupSlugifier::class)
        ->private()
        ->args([new Reference('sulu.content.path_cleaner')]);

    $services->set('sulu_document_manager.node_name_slugifier', NodeNameSlugifier::class)
        ->private()
        ->args([new Reference('sulu_document_manager.slugifier')]);

    $services->set('sulu_document_manager.namespace_registry', NamespaceRegistry::class)
        ->private()
        ->args(['%sulu_document_manager.namespace_mapping%']);

    $services->set('sulu_document_manager.property_encoder', PropertyEncoder::class)
        ->public()
        ->args([new Reference('sulu_document_manager.namespace_registry')]);

    $services->set('sulu_document_manager.name_resolver', NameResolver::class)
        ->private();

    $services->set('sulu_document_manager.document_domain_event_collector', DocumentDomainEventCollector::class)
        ->args([new Reference('sulu_activity.domain_event_dispatcher')]);

    $services->alias(DocumentDomainEventCollectorInterface::class, 'sulu_document_manager.document_domain_event_collector');

    $services->set('sulu_document_manager.document_domain_event_collector_subscriber', DocumentDomainEventCollectorSubscriber::class)
        ->args([new Reference('sulu_document_manager.document_domain_event_collector')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.path_segment_registry', PathSegmentRegistry::class)
        ->args(['%sulu_document_manager.segments%']);

    $services->set('sulu_document_manager.path_builder', PathBuilder::class)
        ->private()
        ->args([new Reference('sulu_document_manager.path_segment_registry')]);

    $services->set('sulu_document_manager.document_inspector', DocumentInspector::class)
        ->public()
        ->args([
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_document_manager.path_segment_registry'),
            new Reference('sulu_document_manager.namespace_registry'),
            new Reference('sulu_document_manager.proxy_factory'),
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_core.webspace.webspace_manager'),
        ]);

    $services->set('sulu_document_manager.proxy_factory', ProxyFactory::class)
        ->args([
            new Reference('sulu_document_manager.proxy_manager.factory.ghost'),
            new Reference('sulu_document_manager.event_dispatcher'),
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_document_manager.metadata_factory'),
        ]);

    $services->set('sulu_document_manager.proxy_manager.factory.ghost', LazyLoadingGhostFactory::class)
        ->args([new Reference('sulu_core.proxy_manager.configuration')]);

    $services->set('sulu_document_manager.initializer.root_path_purge_initializer', RootPathPurgeInitializer::class)
        ->args([
            new Reference('doctrine_phpcr'),
            new Reference('sulu_document_manager.path_segment_registry'),
            'base',
        ])
        ->tag('sulu_document_manager.initializer', ['priority' => 250]);

    $services->set('sulu_document_manager.initializer', Initializer::class)
        ->public()
        ->args([
            new Reference('service_container'),
            [],
        ]);

    $services->set('sulu_document_manager.initializer.workspace', WorkspaceInitializer::class)
        ->args([new Reference('doctrine_phpcr')])
        ->tag('sulu_document_manager.initializer', ['priority' => 255]);

    $services->set('sulu_document_manager.subscriber.core.instantiator', InstantiatorSubscriber::class)
        ->args([new Reference('sulu_document_manager.metadata_factory')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.core.registrator', RegistratorSubscriber::class)
        ->args([new Reference('sulu_document_manager.document_registry')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.phpcr.reorder', ReorderSubscriber::class)
        ->args([new Reference('sulu_document_manager.node_helper')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.phpcr.mixin', MixinSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_document_manager.property_encoder'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.phpcr.find', FindSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_document_manager.node_manager'),
            new Reference('sulu_document_manager.event_dispatcher'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.phpcr.query', QuerySubscriber::class)
        ->args([
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.event_dispatcher'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.phpcr.general', GeneralSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_document_manager.node_manager'),
            new Reference('sulu_document_manager.node_helper'),
            new Reference('sulu_document_manager.event_dispatcher'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.phpcr.remove', RemoveSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_document_manager.node_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.core.mapping', MappingSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.proxy_factory'),
            new Reference('sulu_document_manager.document_registry'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.phpcr.refresh', RefreshSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.event_dispatcher'),
            new Reference('sulu_document_manager.document_registry'),
        ])
        ->tag('sulu_document_manager.event_subscriber');
};
