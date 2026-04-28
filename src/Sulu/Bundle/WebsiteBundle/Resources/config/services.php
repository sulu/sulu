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

use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\WebsiteBundle\Admin\Helper\UrlSelect;
use Sulu\Bundle\WebsiteBundle\Admin\WebsiteAdmin;
use Sulu\Bundle\WebsiteBundle\Cache\CacheClearer;
use Sulu\Bundle\WebsiteBundle\Controller\CacheController;
use Sulu\Bundle\WebsiteBundle\Controller\ErrorController;
use Sulu\Bundle\WebsiteBundle\Controller\RedirectController;
use Sulu\Bundle\WebsiteBundle\Controller\SegmentController;
use Sulu\Bundle\WebsiteBundle\Controller\SitemapController;
use Sulu\Bundle\WebsiteBundle\EventListener\RouterListener;
use Sulu\Bundle\WebsiteBundle\EventListener\TranslatorListener;
use Sulu\Bundle\WebsiteBundle\EventSubscriber\DomainEventEventSubscriber;
use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Bundle\WebsiteBundle\Routing\PortalLoader;
use Sulu\Bundle\WebsiteBundle\Routing\RequestListener;
use Sulu\Bundle\WebsiteBundle\Twig\Core\UtilTwigExtension;
use Sulu\Component\Webspace\EventSubscriber\WebspaceTagSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_website.redirect_controller', RedirectController::class)
        ->public()
        ->args([new Reference('router')]);

    $services->alias(RedirectController::class, 'sulu_website.redirect_controller')
        ->public();

    $services->set('sulu_website.sitemap_controller', SitemapController::class)
        ->public()
        ->args([
            new Reference('sulu_website.sitemap.xml_renderer'),
            new Reference('sulu_website.sitemap.pool'),
            new Reference('sulu_website.sitemap.xml_dumper'),
            new Reference('filesystem'),
            new Reference('router'),
            '%sulu_website.sitemap.cache.lifetime%',
            '%kernel.debug%',
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.cache_controller', CacheController::class)
        ->public()
        ->args([
            new Reference('sulu_website.http_cache.clearer'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_security.security_checker'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_website.admin', WebsiteAdmin::class)
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_security.security_checker'),
            new Reference('router'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_website.twig.util', UtilTwigExtension::class)
        ->tag('twig.extension');

    $services->set('sulu_website.routing.portal_loader', PortalLoader::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('file_locator'),
        ])
        ->tag('routing.loader');

    $services->set('sulu_website.resolver.request_analyzer', RequestAnalyzerResolver::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
        ]);

    $services->set('sulu_website.resolver.template_attribute', TemplateAttributeResolver::class)
        ->public()
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_website.resolver.request_analyzer'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('router'),
            new Reference('request_stack'),
            '%kernel.environment%',
        ]);

    $services->alias(TemplateAttributeResolverInterface::class, 'sulu_website.resolver.template_attribute');

    $services->set('sulu_website.routing.request_listener', RequestListener::class)
        ->args([
            new Reference('router'),
            new Reference('sulu_core.webspace.request_analyzer'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_website.error_controller', ErrorController::class)
        ->decorate('error_controller')
        ->args([
            new Reference('sulu_website.error_controller.inner'),
            new Reference('sulu_website.resolver.template_attribute'),
            new Reference('twig'),
            '%kernel.debug%',
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.segment_controller', SegmentController::class)
        ->public()
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            '%sulu_website.segment_cookie_name%',
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_website.http_cache.clearer', CacheClearer::class)
        ->public()
        ->args([
            new Reference('filesystem'),
            '%kernel.environment%',
            new Reference('request_stack'),
            new Reference('event_dispatcher'),
            '%kernel.project_dir%/var',
            new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_http_cache.tags.enabled%',
        ]);

    $services->set('sulu_website.router_listener', RouterListener::class)
        ->decorate('router_listener')
        ->args([
            new Reference('sulu_website.router_listener.inner'),
            new Reference('sulu_core.webspace.request_analyzer'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_website.event_subscriber.cache_clear', DomainEventEventSubscriber::class)
        ->args([
            new Reference('sulu_activity.domain_event_dispatcher'),
            new Reference('sulu_core.webspace.webspace_manager'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_website.event_listener.translator', TranslatorListener::class)
        ->args([new Reference('translator')])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_website.url_select_helper', UrlSelect::class)
        ->public()
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
        ]);

    $services->set('sulu_website.webspace_tag_subscriber', WebspaceTagSubscriber::class)
        ->args([
            new Reference('sulu_http_cache.reference_store', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_core.webspace.request_analyzer'),
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('kernel.event_subscriber');
};
