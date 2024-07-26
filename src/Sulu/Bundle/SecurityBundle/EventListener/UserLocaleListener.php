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

use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * Sets the locale of the current User to the request. Required for the translator to work properly.
 */
class UserLocaleListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private LocaleAwareInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'copyUserLocaleToRequest'];
    }

    /**
     * Sets the locale of the current User to the request, if a User is logged in.
     */
    public function copyUserLocaleToRequest(RequestEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        $locale = $user->getLocale();
        $event->getRequest()->setLocale($locale);
        $this->translator->setLocale($locale);
    }
}
