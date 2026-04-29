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

use Sulu\Bundle\PageBundle\Serializer\Handler\ExtensionContainerHandler;
use Sulu\Bundle\PageBundle\Serializer\Handler\StructureHandler;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\ExtensionContainerSubscriber;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\LocaleSubscriber;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\ParentSubscriber;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\PathSubscriber;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\RedirectTypeSubscriber;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\ShadowLocaleSubscriber;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\StructureSubscriber;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\WorkflowStageSubscriber;
use Sulu\Component\Content\Compat\Serializer\PageBridgeHandler;
use Sulu\Component\Content\Compat\Serializer\PageBridgeSubscriber;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.document.serializer.handler.structure', StructureHandler::class)
        ->tag('jms_serializer.subscribing_handler');

    $services->set('sulu_page.document.serializer.handler.extension_container', ExtensionContainerHandler::class)
        ->tag('jms_serializer.subscribing_handler');

    $services->set('sulu_page.document.serializer.subscriber.structure_behavior', StructureSubscriber::class)
        ->args([new Reference('sulu_document_manager.document_inspector')])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.document.serializer.subscriber.extension_container', ExtensionContainerSubscriber::class)
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.document.serializer.subscriber.path_behavior', PathSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_registry'),
        ])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.document.serializer.subscriber.parent_behavior', ParentSubscriber::class)
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.document.serializer.subscriber.locale', LocaleSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_registry'),
        ])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.document.serializer.subscriber.shadow_locale_behavior', ShadowLocaleSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_registry'),
        ])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.document.serializer.subscriber.redirect_type_behavior', RedirectTypeSubscriber::class)
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.document.serializer.subscriber.workflow_stage_behavior', WorkflowStageSubscriber::class)
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_page.compat.serializer.handler.page_bridge', PageBridgeHandler::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_page.compat.structure.legacy_property_factory'),
            new Reference('sulu_page.structure.factory'),
        ])
        ->tag('jms_serializer.subscribing_handler');

    $services->set('sulu_page.compat.serializer.subscriber.page_bridge', PageBridgeSubscriber::class)
        ->tag('jms_serializer.event_subscriber');
};
