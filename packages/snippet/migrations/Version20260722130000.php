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

namespace Sulu\Snippet\Migrations;

use Sulu\Component\Persistence\Migrations\AbstractLengthMigration;

final class Version20260722130000 extends AbstractLengthMigration
{
    public function getDescription(): string
    {
        return 'Widen sn_snippet_dimension_contents.templateKey column length';
    }

    protected function getWidenedLengths(): array
    {
        return [
            'sn_snippet_dimension_contents' => [
                'templateKey' => 64,
            ],
        ];
    }

    protected function getLegacyLengths(): array
    {
        return [
            'sn_snippet_dimension_contents' => [
                'templateKey' => 31,
            ],
        ];
    }
}
