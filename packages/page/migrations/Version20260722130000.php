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

namespace Sulu\Page\Migrations;

use Sulu\Component\Persistence\Migrations\AbstractLengthMigration;

final class Version20260722130000 extends AbstractLengthMigration
{
    public function getDescription(): string
    {
        return 'Widen pa_pages.webspaceKey, pa_page_dimension_content_navigation_contexts.name '
            . 'and pa_page_dimension_contents.templateKey column lengths';
    }

    protected function getWidenedLengths(): array
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

    protected function getLegacyLengths(): array
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
