<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\EventListener;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * This listener sets the current portal on the portal manager.
 */
class RequestListener
{
    /**
     * @var \Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface
     */
    protected $requestAnalyzer;

    public function __construct(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            $this->requestAnalyzer->analyze($event->getRequest());
        }
    }
}
