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
use Sulu\Bundle\SecurityBundle\EventListener\UserLocaleListener;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\Translator;

class UserLocaleListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TokenInterface>
     */
    private $token;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var ObjectProphecy<Translator>
     */
    private $translator;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var RequestEvent
     */
    private $event;

    /**
     * @var UserLocaleListener
     */
    private $userLocaleListener;

    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $kernel;

    public function setUp(): void
    {
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->request = $this->prophesize(Request::class);

        $this->event = new RequestEvent(
            $this->kernel->reveal(),
            $this->request->reveal(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->token = $this->prophesize(TokenInterface::class);

        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());

        $this->translator = $this->prophesize(Translator::class);

        $this->userLocaleListener = new UserLocaleListener($this->tokenStorage->reveal(), $this->translator->reveal());
    }

    public function testCopyUserLocaleToRequestWithoutUser(): void
    {
        $this->request->setLocale(Argument::any())->shouldNotBeCalled();
        $this->userLocaleListener->copyUserLocaleToRequest($this->event);
        $this->translator->setLocale(Argument::any())->shouldNotBeCalled();
    }

    public function testCopyUserLocaleToRequest(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getLocale()->willReturn('de');
        $this->token->getUser()->willReturn($user);

        $this->request->setLocale('de')->shouldBeCalled();
        $this->translator->setLocale('de')->shouldBeCalled();
        $this->userLocaleListener->copyUserLocaleToRequest($this->event);
    }
}
