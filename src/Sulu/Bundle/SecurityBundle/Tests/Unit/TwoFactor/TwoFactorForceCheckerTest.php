<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\TwoFactor;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\SecurityBundle\TwoFactor\TwoFactorForceChecker;

class TwoFactorForceCheckerTest extends TestCase
{
    public function testIsForcedWithoutPattern(): void
    {
        $checker = new TwoFactorForceChecker(null, true, ['totp']);

        $this->assertFalse($checker->isForced($this->createUser('admin@sulu.io')));
    }

    public function testIsForcedMatchingPattern(): void
    {
        $checker = new TwoFactorForceChecker('/^(.*)@sulu\.io$/', true, ['totp']);

        $this->assertTrue($checker->isForced($this->createUser('admin@sulu.io')));
        $this->assertFalse($checker->isForced($this->createUser('admin@localhost')));
    }

    public function testIsSetupRequiredWithoutSetupMode(): void
    {
        $checker = new TwoFactorForceChecker('/(.+)/', false, ['totp']);

        $this->assertFalse($checker->isSetupRequired($this->createUser('admin@sulu.io')));
    }

    public function testIsSetupRequiredWithoutMethod(): void
    {
        $checker = new TwoFactorForceChecker('/(.+)/', true, ['totp']);

        $this->assertTrue($checker->isSetupRequired($this->createUser('admin@sulu.io')));
    }

    public function testIsSetupRequiredWithUnavailableMethod(): void
    {
        $checker = new TwoFactorForceChecker('/(.+)/', true, ['totp']);
        $user = $this->createUser('admin@sulu.io', 'email');

        $this->assertTrue($checker->isSetupRequired($user));
    }

    public function testIsSetupRequiredWithPendingSecretOnly(): void
    {
        $checker = new TwoFactorForceChecker('/(.+)/', true, ['totp']);
        $user = $this->createUser('admin@sulu.io', 'totp', ['pendingTotpSecret' => 'PENDING']);

        $this->assertTrue($checker->isSetupRequired($user));
    }

    public function testIsSetupRequiredWithConfirmedSecret(): void
    {
        $checker = new TwoFactorForceChecker('/(.+)/', true, ['totp']);
        $user = $this->createUser('admin@sulu.io', 'totp', ['totpSecret' => 'CONFIRMED']);

        $this->assertFalse($checker->isSetupRequired($user));
    }

    public function testIsSetupRequiredWithMethodWithoutSecret(): void
    {
        $checker = new TwoFactorForceChecker('/(.+)/', true, ['email']);
        $user = $this->createUser('admin@sulu.io', 'email');

        $this->assertFalse($checker->isSetupRequired($user));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createUser(string $email, ?string $method = null, array $options = []): User
    {
        $user = new User();
        $user->setEmail($email);

        if ($method) {
            $twoFactor = new UserTwoFactor($user);
            $twoFactor->setMethod($method);
            $twoFactor->setOptions($options);
            $user->setTwoFactor($twoFactor);
        }

        return $user;
    }
}
