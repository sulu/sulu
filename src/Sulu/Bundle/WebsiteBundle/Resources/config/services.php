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
use Sulu\Bundle\WebsiteBundle\Controller\DefaultController;
use Sulu\Bundle\WebsiteBundle\Controller\ErrorController;
use Sulu\Bundle\WebsiteBundle\Controller\RedirectController;
use Sulu\Bundle\WebsiteBundle\Controller\SegmentController;
use Sulu\Bundle\WebsiteBundle\Controller\SitemapController;
use Sulu\Bundle\WebsiteBundle\EventListener\RouterListener;
use Sulu\Bundle\WebsiteBundle\EventListener\SecurityListener;
use Sulu\Bundle\WebsiteBundle\EventListener\TranslatorListener;
use Sulu\Bundle\WebsiteBundle\EventSubscriber\DomainEventEventSubscriber;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapper;
use Sulu\Bundle\WebsiteBundle\Navigation\NavigationQueryBuilder;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePool;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\WebspaceReferenceStore;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Bundle\WebsiteBundle\Routing\PortalLoader;
use Sulu\Bundle\WebsiteBundle\Routing\RequestListener;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapContentQueryBuilder;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGenerator;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Content\MemoizedContentTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Core\UtilTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Meta\MetaTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Navigation\MemoizedNavigationTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Seo\SeoTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Sitemap\MemoizedSitemapTwigExtension;
use Sulu\Bundle\WebsiteBundle\Twig\Sitemap\SitemapTwigExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_website.admin.class', WebsiteAdmin::class);
    $parameters->set('sulu_website.navigation_mapper.class', NavigationMapper::class);
    $parameters->set('sulu_website.sitemap.class', SitemapGenerator::class);
    $parameters->set('sulu_website.twig.content_path.class', ContentPathTwigExtension::class);
    $parameters->set('sulu_website.twig.navigation.class', NavigationTwigExtension::class);
    $parameters->set('sulu_website.twig.navigation.memoized.class', MemoizedNavigationTwigExtension::class);
    $parameters->set('sulu_website.twig.sitemap.class', SitemapTwigExtension::class);
    $parameters->set('sulu_website.twig.sitemap.memoized.class', MemoizedSitemapTwigExtension::class);
    $parameters->set('sulu_website.twig.content.class', ContentTwigExtension::class);
    $parameters->set('sulu_website.twig.content.memoized.class', MemoizedContentTwigExtension::class);
    $parameters->set('sulu_website.twig.meta.class', MetaTwigExtension::class);
    $parameters->set('sulu_website.twig.seo.class', SeoTwigExtension::class);
    $parameters->set('sulu_website.twig.util.class', UtilTwigExtension::class);
    $parameters->set('sulu_website.routing.portal_loader.class', PortalLoader::class);
    $parameters->set('sulu_website.resolver.request_analyzer.class', RequestAnalyzerResolver::class);
    $parameters->set('sulu_website.resolver.structure.class', StructureResolver::class);
    $parameters->set('sulu_website.resolver.parameter.class', ParameterResolver::class);
    $parameters->set('sulu_website.navigation_mapper.query_builder.class', NavigationQueryBuilder::class);
    $parameters->set('sulu_website.sitemap.query_builder.class', SitemapContentQueryBuilder::class);

    $services->set('sulu_website.redirect_controller', RedirectController::class)
        ->public()
        ->args([new Reference('router')]);

    $services->set('sulu_website.default_controller', DefaultController::class)
        ->public()
        ->tag('container.service_subscriber')
        ->tag('controller.service_arguments')
        ->tag('sulu.context', ['context' => 'website'])
        ->call('setContainer', [new Reference(ContainerInterface::class)]);

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

    $services->set('sulu_website.admin', '%sulu_website.admin.class%')
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_security.security_checker'),
            new Reference('router'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_website.navigation_mapper.query_builder', '%sulu_website.navigation_mapper.query_builder.class%')
        ->private()
        ->args([
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            '%sulu.content.language.namespace%',
        ]);

    $services->set('sulu_website.navigation_mapper', '%sulu_website.navigation_mapper.class%')
        ->args([
            new Reference('sulu.content.mapper'),
            new Reference('sulu.content.query_executor'),
            new Reference('sulu_website.navigation_mapper.query_builder'),
            new Reference('sulu.phpcr.session'),
            new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_security.permissions%',
            '%sulu_website.enabled_twig_attributes%',
        ]);

    $services->set('sulu_website.sitemap.query_builder', '%sulu_website.sitemap.query_builder.class%')
        ->private()
        ->args([
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            '%sulu.content.language.namespace%',
        ]);

    $services->set('sulu_website.sitemap', '%sulu_website.sitemap.class%')
        ->args([
            new Reference('sulu.content.query_executor'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_website.sitemap.query_builder'),
            '%kernel.environment%',
        ]);

    $services->set('sulu_website.twig.content_path', '%sulu_website.twig.content_path.class%')
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
            new Reference('sulu_core.webspace.request_analyzer', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        ])
        ->tag('twig.extension');

    $services->set('sulu_website.twig.navigation', '%sulu_website.twig.navigation.class%')
        ->args([
            new Reference('sulu.content.mapper'),
            new Reference('sulu_website.navigation_mapper'),
            new Reference('sulu_core.webspace.request_analyzer', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_website.twig.navigation.memoized', '%sulu_website.twig.navigation.memoized.class%')
        ->args([
            new Reference('sulu_website.twig.navigation'),
            new Reference('sulu_core.cache.memoize'),
            '%sulu_website.navigation.cache.lifetime%',
        ])
        ->tag('twig.extension');

    $services->set('sulu_website.twig.sitemap', '%sulu_website.twig.sitemap.class%')
        ->args([
            new Reference('sulu_website.sitemap'),
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
            new Reference('sulu_core.webspace.request_analyzer', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_website.twig.sitemap.memoized', '%sulu_website.twig.sitemap.memoized.class%')
        ->args([
            new Reference('sulu_website.twig.sitemap'),
            new Reference('sulu_core.cache.memoize'),
            '%sulu_website.sitemap.cache.lifetime%',
        ])
        ->tag('twig.extension');

    $services->set('sulu_website.twig.content', '%sulu_website.twig.content.class%')
        ->args([
            new Reference('sulu.content.mapper'),
            new Reference('sulu_website.resolver.structure'),
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_security.security_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('request_stack'),
            '%sulu_website.enabled_twig_attributes%',
        ]);

    $services->set('sulu_website.twig.content.memoized', '%sulu_website.twig.content.memoized.class%')
        ->args([
            new Reference('sulu_website.twig.content'),
            new Reference('sulu_core.cache.memoize'),
            '%sulu_website.content.cache.lifetime%',
        ])
        ->tag('twig.extension');

    $services->set('sulu_website.twig.meta', '%sulu_website.twig.meta.class%')
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_website.twig.content_path'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_website.twig.seo', '%sulu_website.twig.seo.class%')
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_website.twig.content_path'),
            new Reference('request_stack'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_website.twig.util', '%sulu_website.twig.util.class%')
        ->tag('twig.extension');

    $services->set('sulu_website.routing.portal_loader', '%sulu_website.routing.portal_loader.class%')
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('file_locator'),
        ])
        ->tag('routing.loader');

    $services->set('sulu_website.resolver.structure', '%sulu_website.resolver.structure.class%')
        ->public()
        ->args([
            new Reference('sulu.content.type_manager'),
            new Reference('sulu_page.extension.manager'),
            '%sulu_website.enabled_twig_attributes%',
        ]);

    $services->set('sulu_website.resolver.request_analyzer', '%sulu_website.resolver.request_analyzer.class%')
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
            '%sulu_website.enabled_twig_attributes%',
        ]);

    $services->alias(TemplateAttributeResolverInterface::class, 'sulu_website.resolver.template_attribute');

    $services->set('sulu_website.resolver.parameter', '%sulu_website.resolver.parameter.class%')
        ->public()
        ->args([
            new Reference('sulu_website.resolver.structure'),
            new Reference('sulu_website.resolver.request_analyzer'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('request_stack'),
            '%sulu_website.segment_switch_url%',
            '%sulu_website.enabled_twig_attributes%',
        ]);

    $services->alias(ParameterResolverInterface::class, 'sulu_website.resolver.parameter');

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

    $services->set('sulu_website.event_listener.security_listener', SecurityListener::class)
        ->args([new Reference('sulu_security.security_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_website.reference_store_pool', ReferenceStorePool::class)
        ->args([tagged_iterator('sulu_website.reference_store', indexAttribute: 'alias')]);

    $services->set('sulu_website.webspace_reference_store', WebspaceReferenceStore::class)
        ->args([new Reference('sulu_core.webspace.request_analyzer', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ->tag('sulu_website.reference_store', ['alias' => 'webspace']);

    $services->set('sulu_website.url_select_helper', UrlSelect::class)
        ->public()
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
        ]);
};
