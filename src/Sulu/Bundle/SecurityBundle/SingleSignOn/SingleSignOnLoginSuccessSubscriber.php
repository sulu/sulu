<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\SingleSignOn;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @final
 *
 * @internal
 *
 * @experimental
 */
class SingleSignOnLoginSuccessSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', 0],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();

        if ('sulu_admin' !== $request->attributes->get('_route')) {
            return;
        }

        if (!$request->query->has('code') || !$request->query->has('state')) {
            return;
        }

        $event->setResponse(new RedirectResponse($request->getPathInfo()));
    }
}
