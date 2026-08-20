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

namespace Sulu\Route\Migrations;

use Sulu\Component\Persistence\Migrations\AbstractLengthMigration;

final class Version20260722130000 extends AbstractLengthMigration
{
    public function getDescription(): string
    {
        return 'Widen ro_routes.webspace and ro_routes.slug column lengths';
    }

    protected function getWidenedLengths(): array
    {
        return [
            'ro_routes' => [
                'webspace' => 64,
                'slug' => 255,
            ],
        ];
    }

    protected function getLegacyLengths(): array
    {
        return [
            'ro_routes' => [
                'webspace' => 31,
                'slug' => 144,
            ],
        ];
    }
}
