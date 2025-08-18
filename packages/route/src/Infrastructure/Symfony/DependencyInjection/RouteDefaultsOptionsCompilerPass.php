<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class RouteDefaultsOptionsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('routing.loader')) {
            return;
        }

        // copy default route options which are set by the symfony FrameworkExtension based on the config:
        // https://github.com/symfony/symfony/pull/31900
        // see also https://github.com/sulu/sulu/pull/5561 / https://github.com/sulu/SuluArticleBundle/issues/521
        $routeDefaultOptions = $container->getDefinition('routing.loader')->getArgument(1);

        Assert::isArray($routeDefaultOptions);

        $container->setParameter('sulu_route.route_default_options', $routeDefaultOptions);

        if ($container->hasDefinition('sulu_route.routing.provider')) {
            $container->getDefinition('sulu_route.routing.provider')->setArgument(5, $routeDefaultOptions); // TODO remove before 3.0 release
        }
    }
}
