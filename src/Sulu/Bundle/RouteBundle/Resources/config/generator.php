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

use Sulu\Bundle\RouteBundle\Generator\ChainRouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\NullRouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\RouteGenerator;
use Sulu\Bundle\RouteBundle\Generator\SymfonyExpressionTokenProvider;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_route.generator.route_generator', RouteGenerator::class)
        ->args([
            new Reference('sulu_route.generator.expression_token_provider'),
            new Reference('sulu_document_manager.slugifier'),
        ])
        ->tag('sulu.route_generator', ['alias' => 'schema']);

    $services->set('sulu_route.generator.null_route_generator', NullRouteGenerator::class)
        ->args([
            new Reference('sulu_route.generator.expression_token_provider'),
            new Reference('sulu_document_manager.slugifier'),
        ])
        ->tag('sulu.route_generator', ['alias' => null]);

    $services->set('sulu_route.generator.expression_token_provider', SymfonyExpressionTokenProvider::class)
        ->args([new Reference('translator')]);

    $services->set('sulu_route.chain_generator', ChainRouteGenerator::class)
        ->args([
            '%sulu_route.mappings%',
            [],
            new Reference('sulu.repository.route'),
        ]);
};
