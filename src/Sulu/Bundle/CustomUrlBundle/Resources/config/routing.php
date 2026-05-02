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

use Sulu\Bundle\CustomUrlBundle\Request\CustomUrlRequestProcessor;
use Sulu\Component\CustomUrl\Routing\CustomUrlRouteProvider;
use Sulu\Component\CustomUrl\Routing\Enhancers\ContentEnhancer;
use Sulu\Component\CustomUrl\Routing\Enhancers\ExternalLinkEnhancer;
use Sulu\Component\CustomUrl\Routing\Enhancers\InternalLinkEnhancer;
use Sulu\Component\CustomUrl\Routing\Enhancers\RedirectEnhancer;
use Sulu\Component\CustomUrl\Routing\Enhancers\SeoEnhancer;
use Sulu\Component\CustomUrl\Routing\Enhancers\StructureEnhancer;
use Sulu\Component\CustomUrl\Routing\Enhancers\TrailingHTMLEnhancer;
use Sulu\Component\CustomUrl\Routing\Enhancers\TrailingSlashEnhancer;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher;
use Symfony\Cmf\Component\Routing\NestedMatcher\UrlMatcher;
use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_custom_urls.uri_filter_regexp', '');

    $services->set('sulu_custom_urls.routing.provider', CustomUrlRouteProvider::class)
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_document_manager.path_builder'),
            '%kernel.environment%',
            [],
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_custom_urls.routing.generator', ProviderBasedGenerator::class)
        ->args([new Reference('sulu_custom_urls.routing.provider')])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('cmf_sulu_custom_urls.final_matcher', UrlMatcher::class)
        ->args([
            new Reference('cmf_routing.matcher.dummy_collection'),
            new Reference('cmf_routing.matcher.dummy_context'),
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_custom_urls.routing.nested_matcher', NestedMatcher::class)
        ->args([
            new Reference('sulu_custom_urls.routing.provider'),
            new Reference('cmf_sulu_custom_urls.final_matcher'),
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_custom_urls.routing.route_enhancers.trailing_slash', TrailingSlashEnhancer::class)
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 100]);

    $services->set('sulu_custom_urls.routing.route_enhancers.trailing_html', TrailingHTMLEnhancer::class)
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 90]);

    $services->set('sulu_custom_urls.routing.route_enhancers.redirect', RedirectEnhancer::class)
        ->args([new Reference('sulu_core.webspace.webspace_manager')])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 80]);

    $services->set('sulu_custom_urls.routing.route_enhancers.seo', SeoEnhancer::class)
        ->args([new Reference('sulu_core.webspace.webspace_manager')])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 70]);

    $services->set('sulu_custom_urls.routing.route_enhancers.content', ContentEnhancer::class)
        ->args([
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.content.structure_manager'),
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 60]);

    $services->set('sulu_custom_urls.routing.route_enhancers.internal_link', InternalLinkEnhancer::class)
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 50]);

    $services->set('sulu_custom_urls.routing.route_enhancers.structure', StructureEnhancer::class)
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 40]);

    $services->set('sulu_custom_urls.routing.route_enhancers.external_link', ExternalLinkEnhancer::class)
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu_custom_urls.route_enhancer', ['priority' => 30]);

    $services->set('sulu_custom_urls.routing.router', DynamicRouter::class)
        ->args([
            new Reference('router.request_context'),
            new Reference('sulu_custom_urls.routing.nested_matcher'),
            new Reference('sulu_custom_urls.routing.generator'),
            '%sulu_custom_urls.uri_filter_regexp%',
            new Reference('event_dispatcher', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_custom_urls.routing.provider'),
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('router', ['priority' => 30]);

    $services->set('sulu_custom_urls.request_processor', CustomUrlRequestProcessor::class)
        ->lazy()
        ->args([
            new Reference('sulu_custom_urls.manager'),
            new Reference('sulu_custom_urls.domain_generator'),
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('sulu.request_attributes', ['priority' => 32]);
};
