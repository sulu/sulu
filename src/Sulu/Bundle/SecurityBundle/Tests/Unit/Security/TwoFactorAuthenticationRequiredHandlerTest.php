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
use Sulu\Bundle\SecurityBundle\Security\TwoFactorAuthenticationRequiredHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;

class TwoFactorAuthenticationRequiredHandlerTest extends TestCase
{
    private TwoFactorAuthenticationRequiredHandler $handler;

    public function setUp(): void
    {
        $this->handler = new TwoFactorAuthenticationRequiredHandler();
    }

    public function testOnAuthenticationRequired(): void
    {
        $response = $this->handler->onAuthenticationRequired(
            Request::create('/', 'POST'),
            new NullToken()
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(
            '{"error": "2fa_required", "completed": false}',
            $response->getContent()
        );
    }
}
