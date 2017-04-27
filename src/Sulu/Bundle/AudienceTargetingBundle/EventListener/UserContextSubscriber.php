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
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $contextUrl;

    /**
     * @var string
     */
    private $contextHitUrl;

    /**
     * @var string
     */
    private $urlHeader;

    /**
     * @var string
     */
    private $referrerHeader;

    /**
     * @var string
     */
    private $httpHeader;

    /**
     * @param \Twig_Environment $twig
     * @param string $contextUrl
     * @param string $contextHitUrl
     * @param string $urlHeader
     * @param string $referrerHeader
     * @param string $userContextHeader
     */
    public function __construct(
        \Twig_Environment $twig,
        $contextUrl,
        $contextHitUrl,
        $urlHeader,
        $referrerHeader,
        $userContextHeader
    ) {
        $this->twig = $twig;
        $this->contextUrl = $contextUrl;
        $this->contextHitUrl = $contextHitUrl;
        $this->urlHeader = $urlHeader;
        $this->referrerHeader = $referrerHeader;
        $this->httpHeader = $userContextHeader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['addUserContextHeaders'],
                ['addUserContextHitScript'],
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

    public function addUserContextHitScript(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $script = $this->twig->render('SuluAudienceTargetingBundle:Template:hit-script.html.twig', [
            'url' => $this->contextHitUrl,
            'urlHeader' => $this->urlHeader,
            'refererHeader' => $this->referrerHeader,
        ]);

        $response->setContent(str_replace(
            '</body>',
            $script . '</body>',
            $response->getContent()
        ));
    }
}
