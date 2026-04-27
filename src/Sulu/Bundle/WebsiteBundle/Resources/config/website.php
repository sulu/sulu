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

use Sulu\Bundle\WebsiteBundle\DataCollector\SuluCollector;
use Sulu\Bundle\WebsiteBundle\EventListener\RedirectExceptionSubscriber;
use Sulu\Bundle\WebsiteBundle\EventListener\SegmentSubscriber;
use Sulu\Bundle\WebsiteBundle\EventSubscriber\GeneratorEventSubscriber;
use Sulu\Bundle\WebsiteBundle\Locale\PortalDefaultLocaleProvider;
use Sulu\Bundle\WebsiteBundle\Locale\RequestDefaultLocaleProvider;
use Sulu\Bundle\WebsiteBundle\Routing\ContentRouteProvider;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_website.data_collector.sulu_collector.class', SuluCollector::class);

    $services->set('sulu_website.data_collector.sulu_collector', '%sulu_website.data_collector.sulu_collector.class%')
        ->args(['%kernel.environment%'])
        ->tag('data_collector', ['template' => '@SuluWebsite/Profiler/layout.html.twig', 'id' => 'sulu'])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.provider.content', ContentRouteProvider::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_core.webspace.request_analyzer'),
            null,
            [],
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.redirect_exception_listener', RedirectExceptionSubscriber::class)
        ->args([
            new Reference('router'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_website.default_locale.provider'),
            new Reference('sulu_core.webspace.webspace_manager.url_replacer'),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.default_locale.portal_provider', PortalDefaultLocaleProvider::class)
        ->args([new Reference('sulu_core.webspace.request_analyzer')])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.default_locale.request_provider', RequestDefaultLocaleProvider::class)
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.event_subscriber.generator', GeneratorEventSubscriber::class)
        ->args(['%sulu.version%'])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.segment_subscriber', SegmentSubscriber::class)
        ->args([
            '%sulu_website.segment_header%',
            new Reference('sulu_core.webspace.request_analyzer'),
            '%sulu_website.segment_cookie_name%',
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'website']);
};
