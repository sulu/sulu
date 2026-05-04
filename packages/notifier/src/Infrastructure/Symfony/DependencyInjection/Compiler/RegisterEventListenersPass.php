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

namespace Sulu\Notifier\Infrastructure\Symfony\DependencyInjection\Compiler;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterEventListenersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sulu_notifier.event_channel_map')) {
            return;
        }

        if (!$container->hasDefinition('sulu_notifier.event_subscriber')) {
            return;
        }

        /** @var array<class-string, list<string>> $map */
        $map = $container->getParameter('sulu_notifier.event_channel_map');
        $definition = $container->getDefinition('sulu_notifier.event_subscriber');

        foreach (\array_keys($map) as $eventClass) {
            if (\is_subclass_of($eventClass, DomainEvent::class) || DomainEvent::class === $eventClass) {
                continue;
            }

            $definition->addTag('kernel.event_listener', [
                'event' => $eventClass,
                'method' => 'onEvent',
                'priority' => -512,
            ]);
        }
    }
}
