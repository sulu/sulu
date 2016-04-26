<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AuthenticationHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthenticationHandler
     */
    private $authenticationHandler;

    /**
     * @var AuthenticationException
     */
    private $exception;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var UserInterface
     */
    private $user;

    public function setUp()
    {
        $this->exception = $this->prophesize(AuthenticationException::class);
        $this->request = $this->prophesize(Request::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(UserInterface::class);

        $this->token->getUser()->willReturn($this->user->reveal());

        $router = $this->prophesize(RouterInterface::class);
        $session = $this->prophesize(Session::class);
        $session->get('_security.admin.target_path')->willReturn('/admin/#target/path');
        $session->set(SecurityContextInterface::AUTHENTICATION_ERROR, $this->exception->reveal())->willReturn(null);
        $router->generate('sulu_admin')->willReturn('/admin');
        $router->generate('sulu_admin.login')->willReturn('/admin/login');

        $this->authenticationHandler = new AuthenticationHandler($router->reveal(), $session->reveal(), 'Sulu');
    }

    public function testOnAuthenticationSuccess()
    {
        $this->request->isXmlHttpRequest()->willReturn(false);

        $response = $this->authenticationHandler->onAuthenticationSuccess($this->request->reveal(), $this->token->reveal());

        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testOnAuthenticationSuccessAjax()
    {
        $this->request->isXmlHttpRequest()->willReturn(true);

        $response = $this->authenticationHandler->onAuthenticationSuccess($this->request->reveal(), $this->token->reveal());

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getContent(), true);
        $this->assertEquals('/admin/#target/path', $response['url']);
    }

    public function testOnAuthenticationFailure()
    {
        $this->request->isXmlHttpRequest()->willReturn(false);

        $response = $this->authenticationHandler->onAuthenticationFailure($this->request->reveal(), $this->exception->reveal());

        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testOnAuthenticationFailureAjax()
    {
        $this->request->isXmlHttpRequest()->willReturn(true);

        $response = $this->authenticationHandler->onAuthenticationFailure($this->request->reveal(), $this->exception->reveal());

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
