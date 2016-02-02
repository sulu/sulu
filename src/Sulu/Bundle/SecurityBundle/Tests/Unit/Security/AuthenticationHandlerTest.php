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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AuthenticationHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthenticationHandler
     */
    private $authenticationHandler;

    private $exception;

    public function setUp()
    {
        parent::setUp();

        $this->exception = $this->prophesize('Symfony\Component\Security\Core\Exception\AuthenticationException');
        $router = $this->prophesize('Symfony\Component\Routing\RouterInterface');
        $session = $this->prophesize('Symfony\Component\HttpFoundation\Session\Session');
        $session->get('_security.admin.target_path')->willReturn('/admin/#target/path');
        $session->set(SecurityContextInterface::AUTHENTICATION_ERROR, $this->exception->reveal())->willReturn(null);
        $router->generate('sulu_admin')->willReturn('/admin');
        $router->generate('sulu_admin.login')->willReturn('/admin/login');
        $this->authenticationHandler = new AuthenticationHandler($router->reveal(), $session->reveal());
    }

    public function testOnAuthenticationSuccess()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $request->isXmlHttpRequest()->willReturn(false);
        $response = $this->authenticationHandler->onAuthenticationSuccess($request->reveal(), $token->reveal());
        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testOnAuthenticationSuccessAjax()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $request->isXmlHttpRequest()->willReturn(true);
        $response = $this->authenticationHandler->onAuthenticationSuccess($request->reveal(), $token->reveal());
        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getContent(), true);
        $this->assertEquals('/admin/#target/path', $response['url']);
    }

    public function testOnAuthenticationFailure()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $request->isXmlHttpRequest()->willReturn(false);
        $response = $this->authenticationHandler->onAuthenticationFailure($request->reveal(), $this->exception->reveal());
        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testOnAuthenticationFailureAjax()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $request->isXmlHttpRequest()->willReturn(true);
        $response = $this->authenticationHandler->onAuthenticationFailure($request->reveal(), $this->exception->reveal());
        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(401, $response->getStatusCode());
    }
}
