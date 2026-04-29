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

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProvider;
use Sulu\Bundle\RouteBundle\Routing\RouteProvider;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher;
use Symfony\Cmf\Component\Routing\NestedMatcher\UrlMatcher;
use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_route.routing.uri_filter_regexp', '');

    $services->set('sulu_route.routing.defaults_provider', RouteDefaultsProvider::class)
        ->args([tagged_iterator('sulu_route.defaults_provider')]);

    $services->set('sulu_route.routing.proxy_factory', LazyLoadingValueHolderFactory::class)
        ->args([new Reference('sulu_core.proxy_manager.configuration')]);

    $services->set('sulu_route.routing.provider', RouteProvider::class)
        ->lazy()
        ->args([
            new Reference('sulu.repository.route'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_route.routing.defaults_provider'),
            new Reference('request_stack'),
            new Reference('sulu_route.routing.proxy_factory'),
            [],
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_route.routing.generator', ProviderBasedGenerator::class)
        ->args([new Reference('sulu_route.routing.provider')])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_route.routing.final_matcher', UrlMatcher::class)
        ->args([
            new Reference('cmf_routing.matcher.dummy_collection'),
            new Reference('cmf_routing.matcher.dummy_context'),
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_route.routing.nested_matcher', NestedMatcher::class)
        ->args([
            new Reference('sulu_route.routing.provider'),
            new Reference('sulu_route.routing.final_matcher'),
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_route.routing.router', DynamicRouter::class)
        ->args([
            new Reference('router.request_context'),
            new Reference('sulu_route.routing.nested_matcher'),
            new Reference('sulu_route.routing.generator'),
            '%sulu_route.routing.uri_filter_regexp%',
            new Reference('event_dispatcher', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            new Reference('sulu_route.routing.provider'),
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('router', ['priority' => 20]);
};
