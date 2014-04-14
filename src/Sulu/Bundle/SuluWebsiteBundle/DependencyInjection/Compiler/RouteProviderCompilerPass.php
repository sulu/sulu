<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass, which instantiates the route provider only when the required dependencies exist
 * @package Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler
 */
class RouteProviderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('sulu_core.webspace.request_analyzer')) {
            $container->setDefinition(
                'sulu_website.provider.portal',
                new Definition('Sulu\Bundle\WebsiteBundle\Routing\PortalRouteProvider', array(
                    new Reference('sulu.content.mapper'),
                    new Reference('sulu_core.webspace.request_analyzer'),
                    new Reference('liip_theme.active_theme')
                ))
            );
        }
    }
}
