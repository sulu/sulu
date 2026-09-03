<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\DependencyInjection\Compiler;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TwoFactorCompilerPassTest extends SuluTestCase
{
    public function testParameter(): void
    {
        $this->assertSame(
            [
                'email',
                'totp',
                'google',
                'trusted_devices',
            ],
            static::getContainer()->getParameter('sulu_security.two_factor_methods')
        );
    }

    public function testBackupCodesEnabledParameter(): void
    {
        $this->assertTrue(
            static::getContainer()->getParameter('sulu_security.two_factor_backup_codes_enabled')
        );
    }

    public function testForceSetupParameter(): void
    {
        $this->assertFalse(
            static::getContainer()->getParameter('sulu_security.two_factor_force_setup')
        );
    }
}
