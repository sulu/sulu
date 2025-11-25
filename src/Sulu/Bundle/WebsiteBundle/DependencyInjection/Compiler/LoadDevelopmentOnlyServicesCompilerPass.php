<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\WebsiteBundle\EventListener\NotFoundWelcomeListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal this is an internal class which should not be used by a project
 */
class LoadDevelopmentOnlyServicesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ('dev' !== $container->getParameter('kernel.environment')) {
            return;
        }

        $definition = (new Definition(NotFoundWelcomeListener::class))
            ->addArgument(new Reference('twig'))
            ->addArgument(new Reference(EntityManagerInterface::class))
            ->addTag('kernel.event_listener', ['event' => 'kernel.exception', 'method' => 'renderWelcomePage']);

        $container->addDefinitions([
            'sulu_website.event_listener.not_found_welcome_listener' => $definition,
        ]);
    }
}
