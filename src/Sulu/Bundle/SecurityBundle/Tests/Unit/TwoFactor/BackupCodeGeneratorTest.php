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
use Sulu\Bundle\SecurityBundle\TwoFactor\BackupCodeGenerator;

class BackupCodeGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $backupCodes = (new BackupCodeGenerator())->generate();

        $this->assertCount(10, $backupCodes);
        $this->assertSame($backupCodes, \array_unique($backupCodes));

        foreach ($backupCodes as $backupCode) {
            $this->assertMatchesRegularExpression('/^[0-9A-Z]{8}$/', $backupCode);
        }
    }

    public function testGenerateCount(): void
    {
        $this->assertCount(5, (new BackupCodeGenerator())->generate(5));
    }

    public function testHash(): void
    {
        $backupCodeGenerator = new BackupCodeGenerator();

        $hash = $backupCodeGenerator->hash('ABCD1234');

        $this->assertNotSame('ABCD1234', $hash);
        $this->assertTrue(\password_verify('ABCD1234', $hash));
        $this->assertFalse(\password_verify('ABCD1235', $hash));
    }
}
