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

namespace Sulu\Snippet\Tests\Functional\Migrations;

use Sulu\Content\Tests\Functional\Migrations\AbstractTagNameToIdMigrationTestCase;
use Sulu\Snippet\Migrations\Version20260429120000;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

class Version20260429120000Test extends AbstractTagNameToIdMigrationTestCase
{
    use CreateSnippetTrait;

    protected function getMigrationClass(): string
    {
        return Version20260429120000::class;
    }

    protected function getTable(): string
    {
        return 'sn_snippet_dimension_contents';
    }

    protected function getUuidColumn(): string
    {
        return 'snippetUuid';
    }

    protected function createContent(): string
    {
        $snippet = self::createSnippet([
            'en' => [
                'draft' => [
                    'template' => 'snippet',
                    'title' => 'Migration Test',
                ],
            ],
        ]);

        return $snippet->getUuid();
    }
}
