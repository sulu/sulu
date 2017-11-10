<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit\EventSubscriber;

use Sulu\Component\HttpCache\EventSubscriber\FlushSubscriber;
use Sulu\Component\HttpCache\HandlerFlushInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class FlushSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FlushSubscriber
     */
    private $subscriber;

    /**
     * @var HandlerFlushInterface
     */
    private $handler;

    /**
     * @var PostResponseEvent
     */
    private $postResponseEvent;

    public function setUp()
    {
        parent::setUp();

        $this->postResponseEvent = $this->prophesize(PostResponseEvent::class);
        $this->handler = $this->prophesize(HandlerFlushInterface::class);

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
