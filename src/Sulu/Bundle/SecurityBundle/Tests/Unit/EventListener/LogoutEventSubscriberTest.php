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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\EventListener\LogoutEventSubscriber;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutEventSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private ObjectProphecy $urlGenerator;

    private LogoutEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->urlGenerator->generate('sulu_admin')
            ->willReturn('/admin/');

        $this->subscriber = new LogoutEventSubscriber($this->urlGenerator->reveal());
    }

    public function testLogoutEventWebsiteLogout(): void
    {
        $request = Request::create('/');
        $event = new LogoutEvent($request, new NullToken());
        $response = new Response();

        $event->setResponse($response);

        $this->subscriber->onLogout($event);

        $this->assertSame($response, $event->getResponse());
    }

    public function testLogoutEventUrlGenerator(): void
    {
        $request = Request::create('/admin/logout');
        $event = new LogoutEvent($request, new NullToken());

        $this->subscriber->onLogout($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin/', $response->getTargetUrl());
    }

    public function testLogoutEventAjax(): void
    {
        $request = Request::create('/admin/logout');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $event = new LogoutEvent($request, new NullToken());

        $this->subscriber->onLogout($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{}', $response->getContent());
    }
}
