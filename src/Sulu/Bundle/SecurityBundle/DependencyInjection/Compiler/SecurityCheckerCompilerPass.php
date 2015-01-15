<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SecurityCheckerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('security.context')) {
            $container->setDefinition(
                'sulu_security.security_checker',
                new Definition(
                    'Sulu\Bundle\SecurityBundle\Permission\SecurityChecker',
                    array(
                        new Reference('security.context')
                    )
                )
            );

            $securityListener = new Definition(
                'Sulu\Bundle\SecurityBundle\EventListener\SuluSecurityListener',
                array(
                    new Reference('sulu_security.security_checker'),
                )
            );

            $securityListener->addTag(
                'kernel.event_listener',
                array(
                    'event' => 'kernel.controller',
                    'method' => 'onKernelController',
                )
            );

            $container->setDefinition(
                'sulu_security.event_listener.security',
                $securityListener
            );
        }
    }
} 
