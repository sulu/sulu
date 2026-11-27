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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Sulu\Bundle\AdminBundle\EventListener\WebsiteEditCookieSubscriber;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class WebsiteEditCookieSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testLoginSetsCookie(): void
    {
        $response = new Response();
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator());

        $subscriber->onLoginSuccess($this->createLoginSuccessEvent($this->createAuthenticatedToken(), $response));

        $cookie = $this->findCookie($response);
        $this->assertNotNull($cookie);
        $this->assertSame('https://sulu.io/admin/resource-view-url-redirect', $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame(0, $cookie->getExpiresTime());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertFalse($cookie->isSecure());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function testLoginUsesSessionCookieLifetime(): void
    {
        $response = new Response();
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator(), ['cookie_lifetime' => 3600]);

        $subscriber->onLoginSuccess($this->createLoginSuccessEvent($this->createAuthenticatedToken(), $response));

        $cookie = $this->findCookie($response);
        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(\time() + 3600, $cookie->getExpiresTime(), 5);
    }

    public function testLoginFallsBackToIniCookieLifetime(): void
    {
        $iniLifetime = \ini_get('session.cookie_lifetime');
        \ini_set('session.cookie_lifetime', '7200');

        try {
            $response = new Response();
            $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator());

            $subscriber->onLoginSuccess($this->createLoginSuccessEvent($this->createAuthenticatedToken(), $response));
        } finally {
            \ini_set('session.cookie_lifetime', (string) $iniLifetime);
        }

        $cookie = $this->findCookie($response);
        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(\time() + 7200, $cookie->getExpiresTime(), 5);
    }

    public function testLoginOverHttpsSetsSecureCookie(): void
    {
        $response = new Response();
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator());

        $subscriber->onLoginSuccess($this->createLoginSuccessEvent(
            $this->createAuthenticatedToken(),
            $response,
            Request::create('https://sulu.io/admin/login', 'POST'),
        ));

        $this->assertTrue($this->findCookie($response)?->isSecure());
    }

    public function testSubscribedEventPriorities(): void
    {
        $this->assertSame(
            [
                LoginSuccessEvent::class => ['onLoginSuccess', -16],
                LogoutEvent::class => ['onLogout', 0],
            ],
            WebsiteEditCookieSubscriber::getSubscribedEvents(),
            'Login must run after the single sign-on subscriber (priority 0) has replaced the response.',
        );
    }

    public function testLoginUsesSessionCookieDomain(): void
    {
        $response = new Response();
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator(), ['cookie_domain' => '.sulu.io']);

        $subscriber->onLoginSuccess($this->createLoginSuccessEvent($this->createAuthenticatedToken(), $response));

        $this->assertSame('.sulu.io', $this->findCookie($response)?->getDomain());
    }

    /**
     * @return iterable<string, array{bool|string|null, string, bool}>
     */
    public static function provideSecureSettings(): iterable
    {
        yield 'auto over http' => ['auto', 'http://sulu.io/admin/login', false];
        yield 'auto over https' => ['auto', 'https://sulu.io/admin/login', true];
        yield 'not configured over https' => [null, 'https://sulu.io/admin/login', true];
        yield 'forced over http' => [true, 'http://sulu.io/admin/login', true];
        yield 'disabled over https' => [false, 'https://sulu.io/admin/login', false];
    }

    #[DataProvider('provideSecureSettings')]
    public function testLoginFollowsSessionCookieSecure(bool|string|null $cookieSecure, string $uri, bool $expected): void
    {
        $response = new Response();
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator(), ['cookie_secure' => $cookieSecure]);

        $subscriber->onLoginSuccess($this->createLoginSuccessEvent(
            $this->createAuthenticatedToken(),
            $response,
            Request::create($uri, 'POST'),
        ));

        $this->assertSame($expected, $this->findCookie($response)?->isSecure());
    }

    public function testLoginWithPendingTwoFactorSetsNoCookie(): void
    {
        $response = new Response();
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator());

        $subscriber->onLoginSuccess($this->createLoginSuccessEvent(
            $this->prophesize(TwoFactorTokenInterface::class)->reveal(),
            $response,
        ));

        $this->assertNull($this->findCookie($response));
    }

    public function testLoginWithoutResponseSetsNoCookie(): void
    {
        $event = $this->createLoginSuccessEvent($this->createAuthenticatedToken(), null);
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator());

        $subscriber->onLoginSuccess($event);

        $this->assertNull($event->getResponse());
    }

    public function testLogoutClearsCookie(): void
    {
        $response = new Response();
        $event = new LogoutEvent(Request::create('/admin/logout'), new NullToken());
        $event->setResponse($response);
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator());

        $subscriber->onLogout($event);

        $cookie = $this->findCookie($response);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isCleared());
        $this->assertSame('/', $cookie->getPath());
    }

    public function testLogoutClearsCookieOnSessionDomain(): void
    {
        $response = new Response();
        $event = new LogoutEvent(Request::create('https://sulu.io/admin/logout'), new NullToken());
        $event->setResponse($response);
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator(), ['cookie_domain' => '.sulu.io', 'cookie_secure' => 'auto']);

        $subscriber->onLogout($event);

        $cookie = $this->findCookie($response);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isCleared());
        $this->assertSame('.sulu.io', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
    }

    public function testLogoutWithoutResponse(): void
    {
        $event = new LogoutEvent(Request::create('/admin/logout'), new NullToken());
        $subscriber = new WebsiteEditCookieSubscriber($this->createUrlGenerator());

        $subscriber->onLogout($event);

        $this->assertNull($event->getResponse());
    }

    private function createUrlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('sulu_admin.resource_view_url_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://sulu.io/admin/resource-view-url-redirect');

        return $urlGenerator->reveal();
    }

    private function createAuthenticatedToken(): TokenInterface
    {
        return new UsernamePasswordToken(new InMemoryUser('admin', null, ['ROLE_USER']), 'admin', ['ROLE_USER']);
    }

    private function createLoginSuccessEvent(
        TokenInterface $token,
        ?Response $response,
        ?Request $request = null,
    ): LoginSuccessEvent {
        return new LoginSuccessEvent(
            $this->prophesize(AuthenticatorInterface::class)->reveal(),
            new SelfValidatingPassport(new UserBadge('admin')),
            $token,
            $request ?? Request::create('/admin/login', 'POST'),
            $response,
            'admin',
        );
    }

    private function findCookie(Response $response): ?Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if (WebsiteEditCookieSubscriber::COOKIE_NAME === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }
}
