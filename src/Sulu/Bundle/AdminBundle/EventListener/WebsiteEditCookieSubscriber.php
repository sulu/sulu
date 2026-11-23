<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\EventListener;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Sets a cookie on path "/" while a user is logged in to the admin, so the website can show the
 * edit button. The admin session cookie is scoped to "/admin" and never reaches the website.
 * The cookie is only a hint: it carries no secret and the admin firewall still guards every request.
 *
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
final class WebsiteEditCookieSubscriber implements EventSubscriberInterface
{
    public const COOKIE_NAME = 'sulu_admin_logged_in';

    /**
     * @param array{cookie_lifetime?: int|string|null, cookie_domain?: string|null, cookie_secure?: bool|string|null} $sessionStorageOptions
     */
    public function __construct(private array $sessionStorageOptions = [])
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Both run after the SecurityBundle subscribers have set the response (single sign-on, logout).
            LoginSuccessEvent::class => ['onLoginSuccess', -16],
            LogoutEvent::class => ['onLogout', 0],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $response = $event->getResponse();
        if (null === $response || $this->isTwoFactorPending($event->getAuthenticatedToken())) {
            return;
        }

        $lifetime = (int) ($this->sessionStorageOptions['cookie_lifetime'] ?? 0);

        $response->headers->setCookie(Cookie::create(
            self::COOKIE_NAME,
            '1',
            $lifetime > 0 ? \time() + $lifetime : 0,
            '/',
            $this->getDomain(),
            $this->isSecure($event->getRequest()),
            false,
            false,
            Cookie::SAMESITE_LAX,
        ));
    }

    public function onLogout(LogoutEvent $event): void
    {
        $response = $event->getResponse();
        if (null === $response) {
            return;
        }

        $response->headers->clearCookie(
            self::COOKIE_NAME,
            '/',
            $this->getDomain(),
            $this->isSecure($event->getRequest()),
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    private function getDomain(): ?string
    {
        $domain = $this->sessionStorageOptions['cookie_domain'] ?? null;

        return null !== $domain && '' !== $domain ? $domain : null;
    }

    // Follows the session cookie: "auto" or no value means secure exactly when the request is.
    private function isSecure(Request $request): bool
    {
        $secure = $this->sessionStorageOptions['cookie_secure'] ?? 'auto';

        return 'auto' === $secure ? $request->isSecure() : (bool) $secure;
    }

    /**
     * The first factor already dispatches a LoginSuccessEvent; the login is complete only with the
     * authenticated token of the second one.
     */
    private function isTwoFactorPending(TokenInterface $token): bool
    {
        return \interface_exists(TwoFactorTokenInterface::class) && $token instanceof TwoFactorTokenInterface;
    }
}
