<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * CompilerPass, which adds the request analyzer as a service if required
 * @package Sulu\Bundle\CoreBundle\DependencyInjection\Compiler
 */
class RequestAnalyzerCompilerPass implements CompilerPassInterface
{

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Check if request analyzer service is enable in configuration
        if ($container->getParameter('sulu_core.webspace.request_analyzer.enabled') == true) {
            // set request analyzer
            $container->setDefinition(
                'sulu_core.webspace.request_analyzer',
                new Definition(
                    'Sulu\Component\Webspace\Analyzer\RequestAnalyzer',
                    array(
                        new Reference('sulu_core.webspace.webspace_manager'),
                        new Reference('sulu_security.user_repository'),
                        $container->getParameter('kernel.environment')
                    )
                )
            );

            // set listener
            $container->setDefinition(
                'sulu_core.webspace.request_listener',
                new Definition(
                    'Sulu\Component\Webspace\EventListener\RequestListener',
                    array(
                        new Reference('sulu_core.webspace.request_analyzer')
                    )
                )
            );

            // add listener to event dispatcher
            $eventDispatcher = $container->findDefinition('event_dispatcher');
            $eventDispatcher->addMethodCall(
                'addListenerService',
                array(
                    'kernel.request',
                    array(
                        'sulu_core.webspace.request_listener',
                        'onKernelRequest'
                    ),
                    $container->getParameter('sulu_core.webspace.request_analyzer.priority')
                )
            );
        }
    }
}
