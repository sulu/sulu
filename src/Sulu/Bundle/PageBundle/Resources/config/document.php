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

use Sulu\Bundle\PageBundle\Document\Subscriber\PublishSubscriber;
use Sulu\Bundle\PageBundle\DocumentManager\ContentInitializer;
use Sulu\Component\Content\Document\Subscriber\Compat\ContentMapperSubscriber;
use Sulu\Component\Content\Document\Subscriber\CopyLocaleSubscriber;
use Sulu\Component\Content\Document\Subscriber\ExtensionSubscriber;
use Sulu\Component\Content\Document\Subscriber\FallbackLocalizationSubscriber;
use Sulu\Component\Content\Document\Subscriber\NavigationContextSubscriber;
use Sulu\Component\Content\Document\Subscriber\OrderSubscriber;
use Sulu\Component\Content\Document\Subscriber\RedirectTypeSubscriber;
use Sulu\Component\Content\Document\Subscriber\ResourceSegmentSubscriber;
use Sulu\Component\Content\Document\Subscriber\RobotSubscriber;
use Sulu\Component\Content\Document\Subscriber\RouteSubscriber;
use Sulu\Component\Content\Document\Subscriber\SecuritySubscriber;
use Sulu\Component\Content\Document\Subscriber\ShadowCopyPropertiesSubscriber;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureRemoveSubscriber;
use Sulu\Component\Content\Document\Subscriber\StructureSubscriber;
use Sulu\Component\Content\Document\Subscriber\TargetSubscriber;
use Sulu\Component\Content\Document\Subscriber\TitleSubscriber;
use Sulu\Component\Content\Document\Subscriber\WebspaceSubscriber;
use Sulu\Component\Content\Document\Subscriber\WorkflowStageSubscriber;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.document.subscriber.content', StructureSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu.content.type_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_page.compat.structure.legacy_property_factory'),
            new Reference('sulu_core.webspace.webspace_manager'),
            '%sulu.content.structure.default_types%',
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_navigationContext.document.subscriber.navigation_context', NavigationContextSubscriber::class)
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_redirect_type.document.subscriber.redirect_type', RedirectTypeSubscriber::class)
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_resource_segment.document.subscriber.resource_segment', ResourceSegmentSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.workflow_stage', WorkflowStageSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.shadow_locale', ShadowLocaleSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_registry'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.shadow_copy_properties', ShadowCopyPropertiesSubscriber::class)
        ->args([new Reference('sulu_document_manager.property_encoder')])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.title', TitleSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.fallback_localization', FallbackLocalizationSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu.content.localization_finder'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.extension', ExtensionSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_page.extension.manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.namespace_registry'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.order', OrderSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.property_encoder'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.security', SecuritySubscriber::class)
        ->args([
            '%sulu_security.permissions%',
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_security.access_control_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.webspace', WebspaceSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_core.webspace.webspace_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.route', RouteSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.node_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.target', TargetSubscriber::class)
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.robot', RobotSubscriber::class)
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.publish', PublishSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_document_manager.node_helper'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_document_manager.metadata_factory'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.copy_locale', CopyLocaleSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.document.subscriber.compat.content_mapper', ContentMapperSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('event_dispatcher'),
            new Reference('sulu.content.mapper'),
            new Reference('sulu.util.node_helper'),
            new Reference('sulu.content.structure_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_document_manager.subscriber.behavior.remove_content', StructureRemoveSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_document_manager.metadata_factory'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_page.document_manager.content_initializer', ContentInitializer::class)
        ->args([
            new Reference('doctrine_phpcr'),
            '%sulu.content.language.namespace%',
        ])
        ->tag('sulu_document_manager.initializer', ['priority' => 127]);
};
