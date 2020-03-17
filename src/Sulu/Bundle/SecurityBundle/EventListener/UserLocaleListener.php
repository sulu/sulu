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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sets the locale of the current User to the request. Required for the translator to work properly.
 */
class UserLocaleListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface|LocaleAwareInterface
     */
    private $translator;

    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator)
    {
        if (!$translator instanceof LocaleAwareInterface && !$translator instanceof LegacyTranslatorInterface) {
            throw new \LogicException(sprintf(
                'Expected "translator" in "%s" to be instance of "%s" or "%s" but "%s" given.',
                __CLASS__,
                LocaleAwareInterface::class,
                LegacyTranslatorInterface::class,
                get_class($translator)
            ));
        }

        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    /**
     * Sets the locale of the current User to the request, if a User is logged in.
     */
    public function copyUserLocaleToRequest(GetResponseEvent $event)
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
