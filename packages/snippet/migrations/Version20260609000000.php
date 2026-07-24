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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shadow locale columns to sn_snippet_dimension_contents';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sn_snippet_dimension_contents');

        if (!$table->hasColumn('shadowLocale')) {
            $table->addColumn('shadowLocale', Types::STRING, ['length' => 15, 'notnull' => false]);
        }

        if (!$table->hasColumn('shadowLocales')) {
            $table->addColumn('shadowLocales', Types::JSON, [
                'notnull' => false,
                'platformOptions' => ['jsonb' => true],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('sn_snippet_dimension_contents');

        if ($table->hasColumn('shadowLocales')) {
            $table->dropColumn('shadowLocales');
        }

        if ($table->hasColumn('shadowLocale')) {
            $table->dropColumn('shadowLocale');
        }
    }
}
