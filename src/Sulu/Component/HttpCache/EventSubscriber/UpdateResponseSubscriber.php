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
use Sulu\Component\HttpCache\StructureCacheHandlerInterface;
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
     * @var StructureCacheHandlerInterface
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
            KernelEvents::REQUEST => 'onRequest',
        );
    }

    /**
     * @param StructureCacheHandlerInterface $handler
     */
    public function __construct(HandlerUpdateResponseInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * If the structure object is present in the request, store a reference
     * to it for later.
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (false === $request->attributes->has('structure')) {
            return;
        }

        // do not cache preview
        if (true === $request->query->has('preview')) {
            return;
        }

        $this->structure = $request->attributes->get('structure');
    }

    /**
     * If the structure has been picked up from the request, updated
     * the response using the configured handler
     *
     * @param FilterResponseEvent
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (null === $this->structure) {
            return;
        }

        if (!$this->handler instanceof HandlerUpdateResponseInterface) {
            return;
        }

        $this->handler->updateResponse($event->getResponse(), $this->structure);
    }
}
