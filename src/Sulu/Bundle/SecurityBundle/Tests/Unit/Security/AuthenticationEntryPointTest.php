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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('sulu_admin')->willReturn('/admin');
        $this->authenticationEntryPoint = new AuthenticationEntryPoint($urlGenerator->reveal());
    }

    public function testStart(): void
    {
        $request = $this->prophesize(Request::class);
        $result = $this->authenticationEntryPoint->start($request->reveal());

        $this->assertEquals(401, $result->getStatusCode());
    }
}
