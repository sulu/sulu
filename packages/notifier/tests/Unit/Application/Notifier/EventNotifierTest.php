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

namespace Sulu\Notifier\Tests\Unit\Application\Notifier;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Notifier\Application\Factory\EventNotificationFactoryInterface;
use Sulu\Notifier\Application\Notifier\EventNotifier;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\NoRecipient;

class EventNotifierTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NotifierInterface>
     */
    private $symfonyNotifier;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private $logger;

    protected function setUp(): void
    {
        $this->symfonyNotifier = $this->prophesize(NotifierInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    public function testNotifyDoesNothingForUnmappedEvent(): void
    {
        $event = new \stdClass();

        $factory = $this->prophesize(EventNotificationFactoryInterface::class);
        $factory->supports(Argument::any())->shouldNotBeCalled();

        $notifier = $this->createNotifier([$factory->reveal()], []);

        $this->symfonyNotifier->send(Argument::any(), Argument::any())->shouldNotBeCalled();

        $notifier->notify($event);
    }

    public function testNotifyUsesFirstSupportingFactory(): void
    {
        $event = new \stdClass();
        $notification = new Notification('subject', ['chat/slack']);

        $first = $this->prophesize(EventNotificationFactoryInterface::class);
        $first->supports($event)->willReturn(false);
        $first->create(Argument::any(), Argument::any())->shouldNotBeCalled();

        $second = $this->prophesize(EventNotificationFactoryInterface::class);
        $second->supports($event)->willReturn(true);
        $second->create($event, ['chat/slack'])->willReturn($notification);

        $third = $this->prophesize(EventNotificationFactoryInterface::class);
        $third->supports(Argument::any())->shouldNotBeCalled();

        $this->symfonyNotifier->send($notification, Argument::type(NoRecipient::class))->shouldBeCalled();

        $notifier = $this->createNotifier(
            [$first->reveal(), $second->reveal(), $third->reveal()],
            [\stdClass::class => ['chat/slack']],
        );

        $notifier->notify($event);
    }

    public function testNotifyLogsAndSwallowsTransportException(): void
    {
        $event = new \stdClass();
        $notification = new Notification('subject', ['chat/slack']);

        $factory = $this->prophesize(EventNotificationFactoryInterface::class);
        $factory->supports($event)->willReturn(true);
        $factory->create($event, ['chat/slack'])->willReturn($notification);

        $this->symfonyNotifier->send($notification, Argument::type(NoRecipient::class))
            ->willThrow(new \RuntimeException('Slack down'));

        $this->logger->error(
            'sulu_notifier dispatch failed',
            Argument::that(function(array $context) {
                return ($context['event'] ?? null) === \stdClass::class
                    && ($context['exception'] ?? null) instanceof \RuntimeException;
            }),
        )->shouldBeCalled();

        $notifier = $this->createNotifier(
            [$factory->reveal()],
            [\stdClass::class => ['chat/slack']],
        );

        $notifier->notify($event); // must not throw
    }

    /**
     * @param list<EventNotificationFactoryInterface> $factories
     * @param array<class-string, list<string>> $eventChannelMap
     */
    private function createNotifier(array $factories, array $eventChannelMap): EventNotifier
    {
        return new EventNotifier(
            $factories,
            $this->symfonyNotifier->reveal(),
            $eventChannelMap,
            $this->logger->reveal(),
        );
    }
}
