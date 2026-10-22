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

use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('dataConvertingBackAndForth')]
    public function testConvertingBackAndForth(int $permissionValue): void
    {
        $permission = $this->maskConverter->convertPermissionsToArray($permissionValue);

        $this->assertSame(
            $permissionValue,
            $this->maskConverter->convertPermissionsToNumber($permission),
        );
    }

    /** @return \Generator<string, array{int}> */
    public static function dataConvertingBackAndForth(): \Generator
    {
        yield 'no permissions' => [0];
        yield 'only test' => [1];
        yield 'only export' => [2];
        yield 'all permissions' => [3];
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

    public function testConvertingPermissions(): void
    {
        $this->maskConverter = new MaskConverter([
            'view' => 64,
            'add' => 32,
            'edit' => 16,
            'delete' => 8,
            'archive' => 4,
            'live' => 2,
            'security' => 1,
        ]);

        $this->assertSame(
            [
                'view' => true,
                'add' => true,
                'edit' => true,
                'delete' => true,
                'archive' => true,
                'live' => true,
                'security' => true,
            ],
            $this->maskConverter->convertPermissionsToArray(127),
        );
    }
}
