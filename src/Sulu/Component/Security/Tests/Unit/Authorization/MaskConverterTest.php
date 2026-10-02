<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Security\Authorization\MaskConverter;
use Sulu\Component\Security\Authorization\MaskConverterInterface;

final class MaskConverterTest extends TestCase
{
    private MaskConverterInterface $maskConverter;

    public function setUp(): void
    {
        $this->maskConverter = new MaskConverter([
            'export' => 2,
            'test' => 1,
        ]);
    }

    public function testConvertingBackAndForth(): void
    {
        $number = 3;
        $permission = $this->maskConverter->convertPermissionsToArray($number);

        $this->assertSame(
            $number,
            $this->maskConverter->convertPermissionsToNumber($permission),
        );
    }

    public function testConvertingToArray(): void
    {
        $number = 3;

        $this->assertSame(
            [
                'export' => true,
                'test' => true,
            ],
            $this->maskConverter->convertPermissionsToArray($number)
        );
    }

    public function testConvertingToNumber(): void
    {
        $permissionData = [
            'export' => true,
            'test' => false,
        ];

        $this->assertSame(
            2,
            $this->maskConverter->convertPermissionsToNumber($permissionData),
        );
    }

    public function testConvertingNoPermission(): void
    {
        $permissionData = [
            'export' => false,
            'test' => false,
        ];

        $this->assertSame(
            $permissionData,
            $this->maskConverter->convertPermissionsToArray(0),
        );
    }
}
