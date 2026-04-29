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

use Sulu\Bundle\DocumentManagerBundle\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\Content\Document\Subscriber\AuthorSubscriber;
use Sulu\Component\Content\Document\Subscriber\BlameSubscriber;
use Sulu\Component\Content\Document\Subscriber\LastModifiedSubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureTypeFilingSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Audit\TimestampSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\ChildrenSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\LocaleSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\NodeNameSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\ParentSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\PathSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\UuidSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AliasFilingSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AutoNameSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\BasePathSubscriber;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\ExplicitSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_document_manager.subscriber.security', SecuritySubscriber::class)
        ->args([new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.behavior.auto_name', AutoNameSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_document_manager.node_name_slugifier'),
            new Reference('sulu_document_manager.name_resolver'),
            new Reference('sulu_document_manager.node_manager'),
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_document_manager.default_session'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.behavior.path.explicit', ExplicitSubscriber::class)
        ->args([new Reference('sulu_document_manager.node_manager')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.blame', BlameSubscriber::class)
        ->args([new Reference('sulu_document_manager.property_encoder')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.author', AuthorSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_document_manager.metadata_factory'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.last_modified', LastModifiedSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_document_manager.metadata_factory'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.timestamp', TimestampSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.node_name', NodeNameSubscriber::class)
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.uuid', UuidSubscriber::class)
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.locale', LocaleSubscriber::class)
        ->args([new Reference('sulu_document_manager.document_registry')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.parent', ParentSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.proxy_factory'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.suscriber.behavior.children', ChildrenSubscriber::class)
        ->args([new Reference('sulu_document_manager.proxy_factory')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.behavior.path', PathSubscriber::class)
        ->args([new Reference('sulu_document_manager.document_inspector')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.behavior.filing', StructureTypeFilingSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.behavior.alias', AliasFilingSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_document_manager.metadata_factory.base'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.behavior.base_path', BasePathSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.node_manager'),
            '/%sulu.content.node_names.base%',
        ])
        ->tag('sulu_document_manager.event_subscriber');
};
