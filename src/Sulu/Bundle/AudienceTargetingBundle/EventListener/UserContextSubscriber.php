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

use Sulu\Bundle\AudienceTargetingBundle\Rule\TargetGroupEvaluatorInterface;
use Sulu\Bundle\AudienceTargetingBundle\UserContext\UserContextStoreInterface;
use Sulu\Component\HttpCache\HttpCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var bool
     */
    private $preview;

    /**
     * @var UserContextStoreInterface
     */
    private $userContextStore;

    /**
     * @var TargetGroupEvaluatorInterface
     */
    private $targetGroupEvaluator;

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
    private $uuidHeader;

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
     * @param bool $preview
     * @param UserContextStoreInterface $userContextStore
     * @param TargetGroupEvaluatorInterface $targetGroupEvaluator
     * @param string $contextUrl
     * @param string $contextHitUrl
     * @param string $urlHeader
     * @param string $referrerHeader
     * @param string $uuidHeader
     * @param string $userContextHeader
     * @param string $userContextCookie
     */
    public function __construct(
        \Twig_Environment $twig,
        $preview,
        UserContextStoreInterface $userContextStore,
        TargetGroupEvaluatorInterface $targetGroupEvaluator,
        $contextUrl,
        $contextHitUrl,
        $urlHeader,
        $referrerHeader,
        $uuidHeader,
        $userContextHeader,
        $userContextCookie
    ) {
        $this->twig = $twig;
        $this->preview = $preview;
        $this->userContextStore = $userContextStore;
        $this->targetGroupEvaluator = $targetGroupEvaluator;
        $this->contextUrl = $contextUrl;
        $this->contextHitUrl = $contextHitUrl;
        $this->urlHeader = $urlHeader;
        $this->referrerHeader = $referrerHeader;
        $this->uuidHeader = $uuidHeader;
        $this->userContextHeader = $userContextHeader;
        $this->userContextCookie = $userContextCookie;
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
                ['addSetCookieHeader'],
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
            $this->userContextStore->setUserContext($userContext);
        } else {
            $targetGroup = $this->targetGroupEvaluator->evaluate();

            $targetGroupId = 0;
            if ($targetGroup) {
                $targetGroupId = $targetGroup->getId();
            }

            $this->userContextStore->updateUserContext($targetGroupId);
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
     * Adds the SetCookie header for the user context, if the user context has changed.
     *
     * @param FilterResponseEvent $event
     */
    public function addSetCookieHeader(FilterResponseEvent $event)
    {
        if (!$this->userContextStore->hasChanged()) {
            return;
        }

        $event->getResponse()->headers->setCookie(
            new Cookie(
                $this->userContextCookie,
                $this->userContextStore->getUserContext(),
                HttpCache::USER_CONTEXT_COOKIE_LIFETIME
            )
        );
    }

    /**
     * Adds a script for triggering an ajax request, which updates the target group on every hit.
     *
     * @param FilterResponseEvent $event
     */
    public function addUserContextHitScript(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->preview
            || strpos($response->headers->get('Content-Type'), 'text/html') !== 0
            || $request->getMethod() !== Request::METHOD_GET
        ) {
            return;
        }

        $script = $this->twig->render('SuluAudienceTargetingBundle:Template:hit-script.html.twig', [
            'url' => $this->contextHitUrl,
            'urlHeader' => $this->urlHeader,
            'refererHeader' => $this->referrerHeader,
            'uuidHeader' => $this->uuidHeader,
            'uuid' => $request->attributes->has('structure') ? $request->attributes->get('structure')->getUuid() : null,
        ]);

        $response->setContent(str_replace(
            '</body>',
            $script . '</body>',
            $response->getContent()
        ));
    }
}
