<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * CompilerPass, which adds the request analyzer as a service if required.
 */
class RequestAnalyzerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // set website request analyzer
        $container->setDefinition(
            'sulu_core.webspace.request_analyzer.website',
            new Definition(
                'Sulu\Component\Webspace\Analyzer\WebsiteRequestAnalyzer',
                [
                    new Reference('sulu_core.webspace.webspace_manager'),
                    $container->getParameter('kernel.environment'),
                ]
            )
        );
        // set admin request analyzer
        $container->setDefinition(
            'sulu_core.webspace.request_analyzer.admin',
            new Definition(
                'Sulu\Component\Webspace\Analyzer\AdminRequestAnalyzer',
                [
                    new Reference('sulu_core.webspace.webspace_manager'),
                    $container->getParameter('kernel.environment'),
                ]
            )
        );

        if ($container->getParameter('sulu.context') === SuluKernel::CONTEXT_WEBSITE) {
            $container->setAlias('sulu_core.webspace.request_analyzer', 'sulu_core.webspace.request_analyzer.website');
        } else {
            $container->setAlias('sulu_core.webspace.request_analyzer', 'sulu_core.webspace.request_analyzer.admin');
        }

        // set listener
        $container->setDefinition(
            'sulu_core.webspace.request_listener',
            new Definition(
                'Sulu\Component\Webspace\EventListener\RequestListener',
                [
                    new Reference('sulu_core.webspace.request_analyzer'),
                ]
            )
        );

        // add listener to event dispatcher
        $eventDispatcher = $container->findDefinition('event_dispatcher');
        $eventDispatcher->addMethodCall(
            'addListenerService',
            [
                'kernel.request',
                [
                    'sulu_core.webspace.request_listener',
                    'onKernelRequest',
                ],
                $container->getParameter('sulu_core.webspace.request_analyzer.priority'),
            ]
        );
    }
}
