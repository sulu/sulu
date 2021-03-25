<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Application\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\EventLogBundle\Application\Subscriber\SetDomainEventUserSubscriber;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Security;

class SetDomainEventUserSubscriberTest extends TestCase
{
    /**
     * @var Security|ObjectProphecy
     */
    private $security;

    public function setUp(): void
    {
        $this->security = $this->prophesize(Security::class);
    }

    public function testSetDomainEventUser()
    {
        $subscriber = $this->createSetDomainEventUserSubscriber();

        $currentUser = $this->prophesize(UserInterface::class);
        $this->security->getUser()->willReturn($currentUser->reveal());

        $event = $this->prophesize(DomainEvent::class);
        $event->getUser()->willReturn(null);
        $event->setUser($currentUser->reveal())->shouldBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    public function testSetDomainEventUserNoUser()
    {
        $subscriber = $this->createSetDomainEventUserSubscriber();

        $this->security->getUser()->willReturn(null);

        $event = $this->prophesize(DomainEvent::class);
        $event->setUser(Argument::cetera())->shouldNotBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    public function testSetDomainEventUserUserAlreadySet()
    {
        $subscriber = new SetDomainEventUserSubscriber(null);

        $event = $this->prophesize(DomainEvent::class);
        $event->setUser(Argument::cetera())->shouldNotBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    public function testSetDomainEventUserNoSecurity()
    {
        $subscriber = $this->createSetDomainEventUserSubscriber();

        $this->security->getUser()->willReturn(null);

        $event = $this->prophesize(DomainEvent::class);
        $event->setUser(Argument::cetera())->shouldNotBeCalled();

        $subscriber->setDomainEventUser($event->reveal());
    }

    private function createSetDomainEventUserSubscriber(): SetDomainEventUserSubscriber
    {
        return new SetDomainEventUserSubscriber(
            $this->security->reveal()
        );
    }
}
