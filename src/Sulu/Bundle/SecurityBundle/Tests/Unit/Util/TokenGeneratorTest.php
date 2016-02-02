<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Util;

class TokenGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateToken()
    {
        $tokenGenerator = new TokenGenerator();
        $token = $tokenGenerator->generateToken();

        $this->assertTrue(is_string($token));
        $this->assertGreaterThan(0, strlen($token));
    }
}
