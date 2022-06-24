<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;

class UserTwoFactorTest extends TestCase
{
    public function testGetSetMethod(): void
    {
        $userTwoFactor = $this->createInstance();

        $this->assertNull($userTwoFactor->getMethod());
        $this->assertSame($userTwoFactor, $userTwoFactor->setMethod('email'));
        $this->assertSame('email', $userTwoFactor->getMethod());
        $this->assertSame($userTwoFactor, $userTwoFactor->setMethod(null));
        $this->assertNull($userTwoFactor->getMethod());
    }

    public function testGetSetOptions(): void
    {
        $userTwoFactor = $this->createInstance();

        $this->assertNull($userTwoFactor->getOptions());
        $this->assertSame($userTwoFactor, $userTwoFactor->setOptions(['authCode' => 'value']));
        $this->assertSame(['authCode' => 'value'], $userTwoFactor->getOptions());
        $this->assertSame($userTwoFactor, $userTwoFactor->setOptions(null));
        $this->assertNull($userTwoFactor->getOptions());
    }

    /**
     * @param array{
     *    user?: TwoFactorInterface,
     * } $data
     */
    private function createInstance(array $data = []): UserTwoFactor
    {
        return new UserTwoFactor($data['user'] ?? new User());
    }
}
