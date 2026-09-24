<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\SecurityBundle\EventListener\ForceTwoFactorSetupSubscriber;
use Sulu\Bundle\SecurityBundle\TwoFactor\TwoFactorForceChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ForceTwoFactorSetupSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testBlocksRequestWhileSetupIsRequired(): void
    {
        $event = $this->handle('sulu_page.get_pages', $this->createUser());

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('two_factor_setup_required', (string) $response->getContent());
    }

    public function testAllowsSetupRoutes(): void
    {
        foreach (ForceTwoFactorSetupSubscriber::ALLOWED_ROUTES as $route) {
            $event = $this->handle($route, $this->createUser());

            $this->assertNull($event->getResponse(), \sprintf('Route "%s" must not be blocked.', $route));
        }
    }

    public function testAllowsSymfonyInternalRoutes(): void
    {
        foreach (['_wdt', '_wdt_stylesheet', '_profiler', '_profiler_home'] as $route) {
            $event = $this->handle($route, $this->createUser());

            $this->assertNull($event->getResponse(), \sprintf('Route "%s" must not be blocked.', $route));
        }
    }

    public function testAllowsRequestWithConfiguredMethod(): void
    {
        $user = $this->createUser();
        $twoFactor = new UserTwoFactor($user);
        $twoFactor->setMethod('totp');
        $twoFactor->setOptions(['totpSecret' => 'CONFIRMED']);
        $user->setTwoFactor($twoFactor);

        $event = $this->handle('sulu_page.get_pages', $user);

        $this->assertNull($event->getResponse());
    }

    public function testAllowsRequestWithoutUser(): void
    {
        $event = $this->handle('sulu_page.get_pages', null);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresSubRequests(): void
    {
        $event = $this->handle('sulu_page.get_pages', $this->createUser(), HttpKernelInterface::SUB_REQUEST);

        $this->assertNull($event->getResponse());
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('admin@sulu.io');

        return $user;
    }

    private function handle(
        string $route,
        ?User $user,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): RequestEvent {
        $tokenStorage = new TokenStorage();
        if ($user) {
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'admin'));
        }

        $subscriber = new ForceTwoFactorSetupSubscriber(
            $tokenStorage,
            new TwoFactorForceChecker('/(.+)/', true, ['totp']),
        );

        $request = new Request();
        $request->attributes->set('_route', $route);

        $event = new RequestEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            $requestType,
        );

        $subscriber->onKernelRequest($event);

        return $event;
    }
}
