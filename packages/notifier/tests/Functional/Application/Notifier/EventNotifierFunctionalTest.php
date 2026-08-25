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
use Sulu\Notifier\Tests\Application\Domain\Event\TestNonDomainEvent;
use Sulu\Notifier\Tests\Application\Notifier\RecordingTransport;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    public function testUntranslatedKeyIsPassedThroughWhenTranslationMissing(): void
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
        self::assertSame('sulu_notifier.subject.test_resource.unmapped_type', $message->getSubject());
        self::assertSame(
            'sulu_activity.description.test_resource.unmapped_type',
            $message->getNotification()?->getContent(),
        );
    }

    public function testDefaultFactoryUsedForNonDomainEvent(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var RecordingTransport $transport */
        $transport = $container->get(RecordingTransport::class);
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $container->get('event_dispatcher');

        $eventDispatcher->dispatch(new TestNonDomainEvent());

        self::assertCount(1, $transport->sent);
        $message = $transport->sent[0];
        self::assertInstanceOf(ChatMessage::class, $message);
        self::assertSame('TestNonDomainEvent', $message->getSubject());
        self::assertSame('Event TestNonDomainEvent occurred', $message->getNotification()?->getContent());
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
