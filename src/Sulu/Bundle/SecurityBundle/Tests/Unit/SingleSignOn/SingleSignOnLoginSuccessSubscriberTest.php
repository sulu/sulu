<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\SingleSignOn;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnLoginSuccessSubscriber;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SingleSignOnLoginSuccessSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame([
            LoginSuccessEvent::class => ['onLoginSuccess', 0],
        ], SingleSignOnLoginSuccessSubscriber::getSubscribedEvents());
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('provideOnLoginSuccessScenarios')]
    public function testOnLoginSuccess(?string $route, array $query, ?string $expectedRedirect): void
    {
        $request = Request::create('/admin/', Request::METHOD_GET, $query);
        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        $event = $this->createLoginSuccessEvent($request);
        (new SingleSignOnLoginSuccessSubscriber())->onLoginSuccess($event);

        $response = $event->getResponse();

        if (null === $expectedRedirect) {
            $this->assertNull($response);

            return;
        }

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expectedRedirect, $response->getTargetUrl());
    }

    /**
     * @return iterable<string, array{0: string|null, 1: array<string, string>, 2: string|null}>
     */
    public static function provideOnLoginSuccessScenarios(): iterable
    {
        yield 'redirects on sulu_admin route with code and state' => [
            'sulu_admin',
            ['code' => 'auth-code', 'state' => 'uuid-state'],
            '/admin/',
        ];

        yield 'ignores non sulu_admin route even with code and state' => [
            'sulu_admin.login_check',
            ['code' => 'auth-code', 'state' => 'uuid-state'],
            null,
        ];

        yield 'ignores sulu_admin route without code' => [
            'sulu_admin',
            ['state' => 'uuid-state'],
            null,
        ];

        yield 'ignores sulu_admin route without state' => [
            'sulu_admin',
            ['code' => 'auth-code'],
            null,
        ];

        yield 'ignores request without route attribute' => [
            null,
            ['code' => 'auth-code', 'state' => 'uuid-state'],
            null,
        ];
    }

    private function createLoginSuccessEvent(Request $request): LoginSuccessEvent
    {
        $authenticator = $this->prophesize(AuthenticatorInterface::class)->reveal();
        $passport = $this->prophesize(Passport::class)->reveal();
        $token = $this->prophesize(TokenInterface::class)->reveal();

        return new LoginSuccessEvent($authenticator, $passport, $token, $request, null, 'admin');
    }
}
