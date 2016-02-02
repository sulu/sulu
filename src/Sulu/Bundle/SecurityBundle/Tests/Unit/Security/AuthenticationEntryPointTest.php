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

class AuthenticationEntryPointTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuthenticationEntryPoint
     */
    private $authenticationEntryPoint;

    public function setUp()
    {
        parent::setUp();

        $urlGenerator = $this->prophesize('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $urlGenerator->generate('sulu_admin.login')->willReturn('/admin/login');
        $this->authenticationEntryPoint = new AuthenticationEntryPoint($urlGenerator->reveal());
    }

    public function provideUrlData()
    {
        return [
            [
                '/admin/api/test',
                401,
            ],
            [
                '/admin/template/test',
                302,
            ],
        ];
    }

    /**
     * @dataProvider provideUrlData
     */
    public function testStart($url, $statusCode)
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $request->getPathInfo()->willReturn($url);
        $request->isXmlHttpRequest()->willReturn(false);
        $result = $this->authenticationEntryPoint->start($request->reveal());

        $this->assertEquals($statusCode, $result->getStatusCode());
    }

    public function testStartAjax()
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $request->isXmlHttpRequest()->willReturn(true);
        $request->getPathInfo()->willReturn(true);
        $result = $this->authenticationEntryPoint->start($request->reveal());

        $this->assertEquals(401, $result->getStatusCode());
    }
}
