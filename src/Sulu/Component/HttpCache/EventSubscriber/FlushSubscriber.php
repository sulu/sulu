<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventSubscriber;

use Sulu\Component\HttpCache\HandlerFlushInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listen to the content mapper and invalidate structures.
 */
class FlushSubscriber implements EventSubscriberInterface
{
    /**
     * @var HandlerFlushInterface
     */
    private $handler;

    /**
     * @param HandlerFlushInterface $handler
     */
    public function __construct(HandlerFlushInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }

    /**
     * Flush the cache on kernel terminate.
     *
     * @param PostResponseEvent $event
     */
    public function onTerminate(PostResponseEvent $event)
    {
        $this->handler->flush();
    }
}
