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

namespace Sulu\Notifier\Tests\Unit\Infrastructure\Symfony\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Notifier\Infrastructure\Symfony\DependencyInjection\Compiler\RegisterEventListenersPass;
use Sulu\Notifier\Tests\Application\Domain\Event\TestNonDomainEvent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterEventListenersPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterEventListenersPass());
    }

    public function testTagsListenerForNonDomainEventClass(): void
    {
        $this->setDefinition('sulu_notifier.event_subscriber', new Definition(\stdClass::class));
        $this->setParameter('sulu_notifier.event_channel_map', [
            TestNonDomainEvent::class => ['chat/slack'],
        ]);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'sulu_notifier.event_subscriber',
            'kernel.event_listener',
            ['event' => TestNonDomainEvent::class, 'method' => 'onEvent', 'priority' => -512],
        );
    }

    public function testDoesNotTagDomainEventSubclass(): void
    {
        $this->setDefinition('sulu_notifier.event_subscriber', new Definition(\stdClass::class));
        $this->setParameter('sulu_notifier.event_channel_map', [
            DomainEvent::class => ['chat/slack'],
        ]);

        $this->compile();

        $tags = $this->container->getDefinition('sulu_notifier.event_subscriber')->getTag('kernel.event_listener');
        self::assertSame([], $tags);
    }

    public function testNoOpWhenParameterMissing(): void
    {
        $this->setDefinition('sulu_notifier.event_subscriber', new Definition(\stdClass::class));

        $this->compile();

        $tags = $this->container->getDefinition('sulu_notifier.event_subscriber')->getTag('kernel.event_listener');
        self::assertSame([], $tags);
    }
}
