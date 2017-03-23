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

use Ramsey\Uuid\Uuid;
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
        $response = $event->getResponse();
        if ($event->getRequest()->getRequestUri() !== $this->contextUrl) {
            $response->setVary($this->httpHeader, false);
        }

        if (!$event->getRequest()->cookies->has($this->cookieName)) {
            $response->headers->setCookie(new Cookie($this->cookieName, Uuid::uuid4()));
        }
    }
}
