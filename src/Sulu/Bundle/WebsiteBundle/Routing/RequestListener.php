<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;

class RequestListener
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @param RouterInterface $router
     * @param RequestAnalyzerInterface $requestAnalyzer
     */
    public function __construct(RouterInterface $router, RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->router = $router;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        $context = $this->router->getContext();
        $portalInformation = $this->requestAnalyzer->getPortalInformation();

        if ($portalInformation) {
            if (!$context->hasParameter('prefix')) {
                $context->setParameter('prefix', $portalInformation->getPrefix());
            }
            if (!$context->hasParameter('host')) {
                $context->setParameter('host', $portalInformation->getHost());
            }
        }
    }
}
