<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class AuthenticationEntryPointTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var AuthenticationEntryPoint
     */
    private $authenticationEntryPoint;

    public function setUp(): void
    {
        parent::setUp();

        $urlGenerator = $this->prophesize('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $urlGenerator->generate('sulu_admin')->willReturn('/admin');
        $this->authenticationEntryPoint = new AuthenticationEntryPoint($urlGenerator->reveal());
    }

    public function testStart(): void
    {
        $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
        $result = $this->authenticationEntryPoint->start($request->reveal());

        $this->assertEquals(401, $result->getStatusCode());
    }
}
