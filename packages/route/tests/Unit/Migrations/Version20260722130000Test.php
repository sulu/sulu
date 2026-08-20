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

namespace Sulu\Route\Tests\Unit\Migrations;

use Sulu\Component\Persistence\Tests\Unit\Migrations\AbstractLengthMigrationTestCase;
use Sulu\Route\Migrations\Version20260722130000;

class Version20260722130000Test extends AbstractLengthMigrationTestCase
{
    protected function getMigrationClass(): string
    {
        return Version20260722130000::class;
    }

    protected function getExpectedWidenedLengths(): array
    {
        return [
            'ro_routes' => [
                'webspace' => 64,
                'slug' => 255,
            ],
        ];
    }

    protected function getExpectedLegacyLengths(): array
    {
        return [
            'ro_routes' => [
                'webspace' => 31,
                'slug' => 144,
            ],
        ];
    }
}
