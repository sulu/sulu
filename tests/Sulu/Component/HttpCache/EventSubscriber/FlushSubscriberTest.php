<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventListener;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Component\HttpCache\EventSubscriber\KernelSubscriber;
use Sulu\Component\HttpCache\HandlerInterface;
use Sulu\Component\HttpCache\EventSubscriber\FlushSubscriber;

class FlushSubscriberTest extends ProphecyTestCase
{
    /**
     * @var KernelSubscriber
     */
    private $subscriber;

    /**
     * @var HandlerInterface
     */
    private $handler;

    public function setUp()
    {
        parent::setUp();

        $this->postResponseEvent = $this->prophesize('Symfony\Component\HttpKernel\Event\PostResponseEvent');
        $this->handler = $this->prophesize('Sulu\Component\HttpCache\HandlerInterface')
            ->willImplement('Sulu\Component\HttpCache\HandlerFlushInterface');

        $this->subscriber = new FlushSubscriber(
            $this->handler->reveal()
        );
        FlushSubscriber::getSubscribedEvents();
    }

    public function testTerminate()
    {
        $this->handler->flush()->shouldBeCalled();
        $this->subscriber->onTerminate($this->postResponseEvent->reveal());
    }
}
