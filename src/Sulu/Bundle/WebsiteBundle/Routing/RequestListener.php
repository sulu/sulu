<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Create your own RequestListener with lower priority if you need to change the behaviour of this one.
 *
 * @internal
 *
 * @final
 */
class RequestListener implements EventSubscriberInterface
{
    /**
     * @var RequestContextAwareInterface
     */
    private $router;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(RequestContextAwareInterface $router, RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->router = $router;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 31]]; // directly after the RouterListener where the portal information is set
    }

    public function onRequest(RequestEvent $event): void
    {
        $context = $this->router->getContext();
        $portalInformation = $this->requestAnalyzer->getPortalInformation();


        if (!$event->isMainRequest()) {
            // sub requests like /_fos_user_context_hash ends in false prefix and should not set anything here
            // else it can happen that the main request get the false prefix and generate urls incorrect.
            return;
        }

        $request = $event->getRequest();

        if ($request->attributes->get('internalRequest', false)) {
            return;
        }

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
