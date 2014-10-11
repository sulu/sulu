<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Prophecy\PhpUnit\ProphecyTestCase;

class AuthenticationEntryPointTest extends ProphecyTestCase
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
        return array(
            array(
                '/admin/api/test',
                401
            ),
            array(
                '/admin/template/test',
                302
            )
        );
    }

    /**
     * @dataProvider provideUrlData
     */
    public function testStart($url, $statusCode)
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $request->getPathInfo()->willReturn($url);
        $result = $this->authenticationEntryPoint->start($request->reveal());

        $this->assertEquals($statusCode, $result->getStatusCode());
    }
}
