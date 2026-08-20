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

namespace Sulu\Page\Tests\Unit\Migrations;

use Sulu\Component\Persistence\Tests\Unit\Migrations\AbstractLengthMigrationTestCase;
use Sulu\Page\Migrations\Version20260722130000;

class Version20260722130000Test extends AbstractLengthMigrationTestCase
{
    protected function getMigrationClass(): string
    {
        return Version20260722130000::class;
    }

    protected function getExpectedWidenedLengths(): array
    {
        return [
            'pa_pages' => [
                'webspaceKey' => 64,
            ],
            'pa_page_dimension_content_navigation_contexts' => [
                'name' => 64,
            ],
            'pa_page_dimension_contents' => [
                'templateKey' => 64,
            ],
        ];
    }

    protected function getExpectedLegacyLengths(): array
    {
        return [
            'pa_pages' => [
                'webspaceKey' => 31,
            ],
            'pa_page_dimension_content_navigation_contexts' => [
                'name' => 31,
            ],
            'pa_page_dimension_contents' => [
                'templateKey' => 31,
            ],
        ];
    }
}
