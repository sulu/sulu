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

use Sulu\Bundle\WebsiteBundle\EventListener\RouterListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideRouterListenerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('router_listener');
        $definition->setClass(RouterListener::class);

        // using setter injection, so that the subclass does not need to change the constructor
        $definition->addMethodCall('setRequestAnalyzer', [new Reference('sulu_core.webspace.request_analyzer')]);
    }
}
