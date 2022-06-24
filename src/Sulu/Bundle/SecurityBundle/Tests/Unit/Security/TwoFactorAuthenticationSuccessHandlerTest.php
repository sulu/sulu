<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\Security\TwoFactorAuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;

class TwoFactorAuthenticationSuccessHandlerTest extends TestCase
{
    private TwoFactorAuthenticationSuccessHandler $handler;

    public function setUp(): void
    {
        $this->handler = new TwoFactorAuthenticationSuccessHandler();
    }

    public function testOnAuthenticationSuccess(): void
    {
        $response = $this->handler->onAuthenticationSuccess(
            Request::create('/', 'POST'),
            new NullToken()
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            '{"completed": true}',
            $response->getContent()
        );
    }
}
