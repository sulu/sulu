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

namespace Sulu\Notifier\Tests\Unit\Application\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Notifier\Application\Notifier\EventNotifier;
use Sulu\Notifier\Application\Subscriber\EventNotificationSubscriber;

class EventNotificationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testSubscribesToDomainEventAtPriorityMinus512(): void
    {
        self::assertSame(
            [DomainEvent::class => ['onEvent', -512]],
            EventNotificationSubscriber::getSubscribedEvents(),
        );
    }

    public function testOnEventDelegatesToNotifier(): void
    {
        $event = $this->prophesize(DomainEvent::class)->reveal();

        $notifier = $this->prophesize(EventNotifier::class);
        $notifier->notify($event)->shouldBeCalled();

        (new EventNotificationSubscriber($notifier->reveal()))->onEvent($event);
    }
}
