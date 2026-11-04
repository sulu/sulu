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

namespace Sulu\Bundle\MediaBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104000000 extends AbstractMigration
{
    private const TABLE = 'me_file_versions';

    public function getDescription(): string
    {
        return 'Add the validFrom and validTo columns to me_file_versions';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $table = $schema->getTable(self::TABLE);

        foreach (['validFrom', 'validTo'] as $column) {
            if (!$table->hasColumn($column)) {
                $table->addColumn($column, Types::DATE_IMMUTABLE, ['notnull' => false]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::TABLE)) {
            return;
        }

        $table = $schema->getTable(self::TABLE);

        foreach (['validFrom', 'validTo'] as $column) {
            if ($table->hasColumn($column)) {
                $table->dropColumn($column);
            }
        }
    }
}
