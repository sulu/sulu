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

namespace Sulu\Article\Migrations;

use Sulu\Component\Persistence\Migrations\AbstractLengthMigration;

final class Version20260722130000 extends AbstractLengthMigration
{
    public function getDescription(): string
    {
        return 'Widen ar_article_dimension_content_additional_webspaces.name '
            . 'and ar_article_dimension_contents.templateKey column lengths';
    }

    protected function getWidenedLengths(): array
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

    protected function getLegacyLengths(): array
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
