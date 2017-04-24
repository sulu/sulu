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

    public function __construct($contextUrl, $httpHeader)
    {
        $this->contextUrl = $contextUrl;
        $this->httpHeader = $httpHeader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                'addUserContextHeaders',
            ],
        ];
    }

    /**
     * Adds the vary header on the response, so that the cache takes the user contexts into account.
     *
     * @param FilterResponseEvent $event
     */
    public function addUserContextHeaders(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getRequestUri() !== $this->contextUrl) {
            $response->setVary($this->httpHeader, false);
        }
    }
}
