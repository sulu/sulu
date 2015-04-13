<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Sulu\Component\HttpCache\HandlerInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;

/**
 * Update the website response with cache headers
 */
class UpdateResponseSubscriber implements EventSubscriberInterface
{
    /**
     * @var HandlerUpdateResponseInterface
     */
    private $handler;

    /**
     * @var StructureInterface
     */
    private $structure;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => 'onResponse',
        );
    }

    /**
     * @param HandlerUpdateResponseInterface $handler
     */
    public function __construct(HandlerUpdateResponseInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * If the structure has been picked up from the request, updated
     * the response using the configured handler
     *
     * @param FilterResponseEvent
     */
    public function onResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()
            || !$this->handler instanceof HandlerUpdateResponseInterface
            || !$request->isMethodSafe()
            || false === $request->attributes->has('structure')
            || true === $request->query->has('preview')
        ) {
            return;
        }

        $this->handler->updateResponse($event->getResponse(), $request->attributes->get('structure'));
    }
}
