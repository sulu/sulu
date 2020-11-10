<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RouteDefaultOptionsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('routing.loader')) {
            return;
        }

        if (!$container->hasDefinition('sulu_route.routing.provider')) {
            return;
        }

        // copy default route options which are set by the symfony FrameworkExtension based on the config:
        // https://github.com/symfony/symfony/pull/31900
        $routeDefaultOptions = $container->getDefinition('routing.loader')->getArgument(1);

        // symfony 4.4 passes the default options as third argument
        if (!\is_array($routeDefaultOptions)) {
            $routeDefaultOptions = $container->getDefinition('routing.loader')->getArgument(2);
        }

        $container->getDefinition('sulu_route.routing.provider')->replaceArgument(
            5,
            $routeDefaultOptions
        );
    }
}
