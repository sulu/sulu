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
        // "google" is not offered although it is enabled in the test application,
        // because it is redundant to the enabled "totp" method
        $this->assertSame(
            [
                'email',
                'totp',
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
}
