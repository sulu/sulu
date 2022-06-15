<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * @internal This class does not provide anything which should be used inside a project.
 *           Register an own event subscriber instead.
 */
final class LogoutEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function onLogout(LogoutEvent $logoutEvent): void
    {
        $adminUrl = $this->urlGenerator->generate('sulu_admin');
        $request = $logoutEvent->getRequest();

        if (!\str_starts_with($request->getPathInfo(), $adminUrl)) {
            // do nothing when not in admin context

            return;
        }

        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse(null, Response::HTTP_OK);
        } else {
            $response = new RedirectResponse($adminUrl);
        }

        $logoutEvent->setResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => ['onLogout', 64]];
    }
}
