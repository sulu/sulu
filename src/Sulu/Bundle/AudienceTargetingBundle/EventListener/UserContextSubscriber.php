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

use Sulu\Bundle\AudienceTargetingBundle\UserContext\UserContextStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserContextSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var UserContextStoreInterface
     */
    private $userContextStore;

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
    private $userContextHeader;

    /**
     * @var string
     */
    private $userContextCookie;

    /**
     * @param \Twig_Environment $twig
     * @param UserContextStoreInterface $userContextStore
     * @param string $contextUrl
     * @param string $contextHitUrl
     * @param string $urlHeader
     * @param string $referrerHeader
     * @param string $userContextHeader
     * @param string $userContextCookie
     */
    public function __construct(
        \Twig_Environment $twig,
        UserContextStoreInterface $userContextStore,
        $contextUrl,
        $contextHitUrl,
        $urlHeader,
        $referrerHeader,
        $userContextHeader,
        $userContextCookie
    ) {
        $this->twig = $twig;
        $this->userContextStore =$userContextStore;
        $this->contextUrl = $contextUrl;
        $this->contextHitUrl = $contextHitUrl;
        $this->urlHeader = $urlHeader;
        $this->referrerHeader = $referrerHeader;
        $this->userContextHeader = $userContextHeader;
        $this->userContextCookie =$userContextCookie;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['setUserContext'],
            ],
            KernelEvents::RESPONSE => [
                ['addVaryHeader'],
                ['addUserContextHitScript'],
            ],
        ];
    }

    /**
     * Evaluates the cookie holding the user context information. This has only an effect if there is no cache used,
     * since in that case the cache already did it.
     *
     * @param GetResponseEvent $event
     */
    public function setUserContext(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $userContext = $request->headers->get($this->userContextHeader) ?: $request->cookies->get($this->userContextCookie);

        if ($userContext) {
            $request->headers->add([$this->userContextHeader => $userContext]);
            $this->userContextStore->setUserContext($userContext);
        }
    }

    /**
     * Adds the vary header on the response, so that the cache takes the user contexts into account.
     *
     * @param FilterResponseEvent $event
     */
    public function addVaryHeader(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getRequestUri() !== $this->contextUrl) {
            $response->setVary($this->userContextHeader, false);
        }
    }

    /**
     * Adds a script for triggering an ajax request, which updates the target group on every hit.
     *
     * @param FilterResponseEvent $event
     */
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
