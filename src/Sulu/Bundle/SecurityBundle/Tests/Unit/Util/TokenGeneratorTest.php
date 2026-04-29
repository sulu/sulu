<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\Util\TokenGenerator;

class TokenGeneratorTest extends TestCase
{
    public function testGenerateToken(): void
    {
        $tokenGenerator = new TokenGenerator();
        $token = $tokenGenerator->generateToken();

        $this->assertTrue(\is_string($token));
        $this->assertGreaterThan(0, \strlen($token));
    }
}
