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

namespace Sulu\Article\Tests\Unit\Migrations;

use Sulu\Article\Migrations\Version20260722130000;
use Sulu\Component\Persistence\Tests\Unit\Migrations\AbstractLengthMigrationTestCase;

class Version20260722130000Test extends AbstractLengthMigrationTestCase
{
    protected function getMigrationClass(): string
    {
        return Version20260722130000::class;
    }

    protected function getExpectedWidenedLengths(): array
    {
        return [
            'ar_article_dimension_content_additional_webspaces' => [
                'name' => 64,
            ],
            'ar_article_dimension_contents' => [
                'templateKey' => 64,
            ],
        ];
    }

    protected function getExpectedLegacyLengths(): array
    {
        return [
            'ar_article_dimension_content_additional_webspaces' => [
                'name' => 31,
            ],
            'ar_article_dimension_contents' => [
                'templateKey' => 31,
            ],
        ];
    }
}
