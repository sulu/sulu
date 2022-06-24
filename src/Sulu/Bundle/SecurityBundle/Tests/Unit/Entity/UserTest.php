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
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;

class UserTest extends TestCase
{
    public function testTwoFactorBackupCodes(): void
    {
        $user = $this->createInstance();
        $user->setTwoFactor(new UserTwoFactor($user));

        $user->addBackUpCode('Code 1');
        $user->addBackUpCode('Code 2');

        $this->assertTrue($user->isBackupCode('Code 1'));
        $this->assertTrue($user->isBackupCode('Code 2'));
        $this->assertFalse($user->isBackupCode('Code False'));

        $user->invalidateBackupCode('Code 1');
        $this->assertFalse($user->isBackupCode('Code 1'));
        $this->assertTrue($user->isBackupCode('Code 2'));
    }

    public function testTwoFactorEmail(): void
    {
        $user = $this->createInstance();
        $user->setEmail('test@localhost');
        $user->setTwoFactor(new UserTwoFactor($user));

        $user->setEmailAuthCode('AuthCode');
        $this->assertSame('AuthCode', $user->getEmailAuthCode());
        $this->assertSame('test@localhost', $user->getEmailAuthRecipient());
    }

    public function testTwoFactorPreferredTwoFactorProvider(): void
    {
        $user = $this->createInstance();
        $user->setTwoFactor(new UserTwoFactor($user));

        $this->assertNull($user->getPreferredTwoFactorProvider());
    }

    public function testTwoFactorTrustedDevices(): void
    {
        $user = $this->createInstance();
        $user->setTwoFactor(new UserTwoFactor($user));

        $this->assertSame(0, $user->getTrustedTokenVersion());
    }

    public function testTwoFactorGoogleAuthenticator(): void
    {
        $user = $this->createInstance();
        $twoFactor = new UserTwoFactor($user);
        $twoFactor->setOptions([
            'googleAuthenticatorSecret' => 'googleSecret',
            'googleAuthenticatorUsername' => 'test@google',
        ]);
        $user->setTwoFactor($twoFactor);

        $this->assertSame('googleSecret', $user->getGoogleAuthenticatorSecret());
        $this->assertSame('test@google', $user->getGoogleAuthenticatorUsername());
    }

    public function testTwoFactorTotp(): void
    {
        $user = $this->createInstance();
        $user->setUsername('test');
        $twoFactor = new UserTwoFactor($user);
        $twoFactor->setOptions([
            'totpSecret' => 'totpSecret',
        ]);
        $user->setTwoFactor($twoFactor);

        $this->assertSame('totpSecret', $user->getTotpAuthenticationConfiguration()->getSecret());
        $this->assertSame('test', $user->getTotpAuthenticationUsername());
    }

    public function createInstance(): User
    {
        return new User();
    }
}
