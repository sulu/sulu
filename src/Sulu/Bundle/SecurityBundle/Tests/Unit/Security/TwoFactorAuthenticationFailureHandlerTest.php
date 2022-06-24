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
use Sulu\Bundle\SecurityBundle\Security\TwoFactorAuthenticationFailureHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TwoFactorAuthenticationFailureHandlerTest extends TestCase
{
    private TwoFactorAuthenticationFailureHandler $handler;

    public function setUp(): void
    {
        $this->handler = new TwoFactorAuthenticationFailureHandler();
    }

    public function testOnAuthenticationFailure(): void
    {
        $response = $this->handler->onAuthenticationFailure(
            Request::create('/', 'POST'),
            new AuthenticationException()
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(
            '{"error": "2fa_failed", "completed": false}',
            $response->getContent()
        );
    }
}
