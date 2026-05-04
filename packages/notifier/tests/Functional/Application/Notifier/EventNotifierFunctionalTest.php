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

namespace Sulu\Notifier\Tests\Functional\Application\Notifier;

use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Notifier\Tests\Application\Domain\Event\TestDomainEvent;
use Sulu\Notifier\Tests\Application\Notifier\RecordingTransport;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Notifier\Message\ChatMessage;

class EventNotifierFunctionalTest extends KernelTestCase
{
    public function testTranslationsRenderedAndDispatched(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var RecordingTransport $transport */
        $transport = $container->get(RecordingTransport::class);
        /** @var DomainEventDispatcherInterface $dispatcher */
        $dispatcher = $container->get(DomainEventDispatcherInterface::class);

        $dispatcher->dispatch(new TestDomainEvent());

        self::assertCount(1, $transport->sent);
        self::assertInstanceOf(ChatMessage::class, $transport->sent[0]);
        self::assertSame('Test created', $transport->sent[0]->getSubject());
    }

    public function testFallbackUsedWhenTranslationMissing(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var RecordingTransport $transport */
        $transport = $container->get(RecordingTransport::class);
        /** @var DomainEventDispatcherInterface $dispatcher */
        $dispatcher = $container->get(DomainEventDispatcherInterface::class);

        $dispatcher->dispatch(new TestDomainEvent('test_resource', 'unmapped_type'));

        self::assertCount(1, $transport->sent);
        $message = $transport->sent[0];
        self::assertInstanceOf(ChatMessage::class, $message);
        self::assertSame('TestDomainEvent', $message->getSubject());
        self::assertSame('Event TestDomainEvent occurred', $message->getNotification()?->getContent());
    }

    public function testTransportFailureIsSwallowed(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var RecordingTransport $transport */
        $transport = $container->get(RecordingTransport::class);
        $transport->throwOnSend = new \RuntimeException('simulated');

        /** @var DomainEventDispatcherInterface $dispatcher */
        $dispatcher = $container->get(DomainEventDispatcherInterface::class);

        // must not throw
        $dispatcher->dispatch(new TestDomainEvent());

        self::assertCount(0, $transport->sent);
    }
}
