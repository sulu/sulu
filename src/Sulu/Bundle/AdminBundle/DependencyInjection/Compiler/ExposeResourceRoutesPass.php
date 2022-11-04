<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass is responsible for adding all configured admin resource
 * routes to `fos_js_routing.routes_to_expose` extension config automatically.
 *
 * @final
 *
 * @internal
 */
class ExposeResourceRoutesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $resources = $container->getParameter('sulu_admin.resources');

        $routeNames = [];

        // Collect all resource routes that have to be exposed
        foreach ($resources as $resource) {
            if (isset($resource['routes'])) {
                foreach ($resource['routes'] as $routeName) {
                    $routeNames[] = $routeName;
                }
            }
        }

        $extractorDefinition = $container->getDefinition('fos_js_routing.extractor');
        $allRouteNames = \array_unique(\array_merge($extractorDefinition->getArgument(1), $routeNames));

        $extractorDefinition->replaceArgument(1, $allRouteNames);
    }
}
