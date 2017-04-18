<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserContextSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $contextUrl;

    /**
     * @var string
     */
    private $httpHeader;

    /**
     * @var string
     */
    private $cookieName;

    public function __construct($contextUrl, $httpHeader, $cookieName)
    {
        $this->contextUrl = $contextUrl;
        $this->httpHeader = $httpHeader;
        $this->cookieName = $cookieName;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'addUserContextHeaders',
        ];
    }

    public function addUserContextHeaders(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getRequestUri() !== $this->contextUrl) {
            $response->setVary($this->httpHeader, false);
        }

        // Necessary because the cookie in the request is faked when the request is passed from the Symfony cache
        // This ensures that the cookie is also set in the browser of the user
        $response->headers->setCookie(new Cookie($this->cookieName, $request->cookies->get($this->cookieName)));
    }
}
