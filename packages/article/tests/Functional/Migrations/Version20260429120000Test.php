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

namespace Sulu\Article\Tests\Functional\Migrations;

use Sulu\Article\Migrations\Version20260429120000;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Content\Tests\Functional\Migrations\AbstractTagNameToIdMigrationTestCase;

class Version20260429120000Test extends AbstractTagNameToIdMigrationTestCase
{
    use CreateArticleTrait;

    protected function getMigrationClass(): string
    {
        return Version20260429120000::class;
    }

    protected function getTable(): string
    {
        return 'ar_article_dimension_contents';
    }

    protected function getUuidColumn(): string
    {
        return 'articleUuid';
    }

    protected function createContent(): string
    {
        $article = self::createArticle([
            'en' => [
                'draft' => [
                    'template' => 'article',
                    'title' => 'Migration Test',
                    'url' => '/migration-test',
                ],
            ],
        ]);

        return $article->getUuid();
    }
}
