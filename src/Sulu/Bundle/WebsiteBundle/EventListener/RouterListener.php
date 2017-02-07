<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener as BaseRouterListener;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener replaces the default RouterListener delivered by Symfony and adds the analyzing of the request as
 * required by Sulu. Validating the result can be deactivated by passing `false` to the `_requestAnalyzer` default in
 * the route.
 */
class RouterListener implements EventSubscriberInterface
{
    const REQUEST_ANALYZER = '_requestAnalyzer';

    /**
     * @var BaseRouterListener
     */
    private $baseRouteListener;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(BaseRouterListener $baseRouterListener, RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->baseRouteListener = $baseRouterListener;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * Analyzes the request before passing the event to the default RouterListener from symfony and validates the result
     * afterwards.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // This call is required in all cases, because the default router needs our webspace information
        // Would be nice to also only call this if the _requestAnalyzer attribute is set, but it's set on the next line
        $this->requestAnalyzer->analyze($request);
        $this->baseRouteListener->onKernelRequest($event);
        if ($request->attributes->get(static::REQUEST_ANALYZER, true) !== false) {
            $this->requestAnalyzer->validate($request);
        }
    }

    /**
     * Simply pass the event to the route listener, because we have nothing to add here.
     *
     * @param FinishRequestEvent $event
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        $this->baseRouteListener->onKernelFinishRequest($event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 32]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
        ];
    }
}
