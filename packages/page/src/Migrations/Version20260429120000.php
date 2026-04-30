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

use Sulu\Content\Migrations\AbstractTagNameToIdMigration;

final class Version20260429120000 extends AbstractTagNameToIdMigration
{
    protected function getTable(): string
    {
        return 'pa_page_dimension_contents';
    }
}
