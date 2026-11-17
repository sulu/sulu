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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110120000 extends AbstractMigration
{
    private const TABLE = 'ar_article_dimension_contents';

    private const INDEX = 'idx_ar_article_dimension_contents_excerpt_priority';

    public function getDescription(): string
    {
        return 'Add excerptPriority column and index to ar_article_dimension_contents';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $table = $schema->getTable(self::TABLE);

        if (!$table->hasColumn('excerptPriority')) {
            $table->addColumn('excerptPriority', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
            ]);
        }

        if (!$table->hasIndex(self::INDEX)) {
            $table->addIndex(['excerptPriority'], self::INDEX);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $table = $schema->getTable(self::TABLE);

        if ($table->hasIndex(self::INDEX)) {
            $table->dropIndex(self::INDEX);
        }

        if ($table->hasColumn('excerptPriority')) {
            $table->dropColumn('excerptPriority');
        }
    }
}
