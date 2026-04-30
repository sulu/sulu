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

namespace Sulu\Page\Tests\Functional\Migrations;

use Sulu\Content\Tests\Functional\Migrations\AbstractTagNameToIdMigrationTestCase;
use Sulu\Page\Migrations\Version20260429120000;
use Sulu\Page\Tests\Traits\CreatePageTrait;

class Version20260429120000Test extends AbstractTagNameToIdMigrationTestCase
{
    use CreatePageTrait;

    protected function getMigrationClass(): string
    {
        return Version20260429120000::class;
    }

    protected function getTable(): string
    {
        return 'pa_page_dimension_contents';
    }

    protected function getUuidColumn(): string
    {
        return 'pageUuid';
    }

    protected function createContent(): string
    {
        $page = $this->createPage([
            'en' => [
                'draft' => [
                    'template' => 'default',
                    'title' => 'Migration Test',
                    'url' => '/migration-test',
                ],
            ],
        ]);

        return $page->getUuid();
    }
}
