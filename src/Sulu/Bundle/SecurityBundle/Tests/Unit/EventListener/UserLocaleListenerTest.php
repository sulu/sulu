<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\EventListener\UserLocaleListener;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserLocaleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var GetResponseEvent
     */
    private $event;

    /**
     * @var UserLocaleListener
     */
    private $userLocaleListener;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class);
        $this->event = $this->prophesize(GetResponseEvent::class);
        $this->event->getRequest()->willReturn($this->request->reveal());

        $this->token = $this->prophesize(TokenInterface::class);

        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());

        $this->userLocaleListener = new UserLocaleListener($this->tokenStorage->reveal());
    }

    public function testCopyUserLocaleToRequestWithoutUser()
    {
        $this->request->setLocale(Argument::any())->shouldNotBeCalled();
        $this->userLocaleListener->copyUserLocaleToRequest($this->event->reveal());
    }

    public function testCopyUserLocaleToRequest()
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getLocale()->willReturn('de');
        $this->token->getUser()->willReturn($user);

        $this->request->setLocale('de')->shouldBeCalled();
        $this->userLocaleListener->copyUserLocaleToRequest($this->event->reveal());
    }
}
