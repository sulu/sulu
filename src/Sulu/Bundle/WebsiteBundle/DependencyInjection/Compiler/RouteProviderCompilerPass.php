<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler;

use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass, which instantiates the route provider only when the required dependencies exist.
 */
class RouteProviderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('sulu.context') === SuluKernel::CONTEXT_WEBSITE) {
            $container->setDefinition(
                'sulu_website.provider.content',
                new Definition('Sulu\Bundle\WebsiteBundle\Routing\ContentRouteProvider', [
                    new Reference('sulu.content.mapper'),
                    new Reference('sulu_core.webspace.request_analyzer'),
                ])
            );
        }
    }
}
