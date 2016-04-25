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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener as BaseRouterListener;

/**
 * This Listener analyzes the request passed to Sulu.
 */
class RouterListener extends BaseRouterListener
{
    const REQUEST_ANALYZER = '_requestAnalyzer';

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $this->requestAnalyzer->analyze($request);
        parent::onKernelRequest($event);
        if ($request->attributes->get(static::REQUEST_ANALYZER, true) !== false) {
            $this->requestAnalyzer->validate($request);
        }
    }

    public function setRequestAnalyzer(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }
}
