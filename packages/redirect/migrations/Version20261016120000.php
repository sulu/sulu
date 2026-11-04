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

namespace Sulu\Redirect\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016120000 extends AbstractMigration
{
    private const REDIRECTS_TABLE = 'rd_redirects';

    private const USERS_TABLE = 'se_users';

    public function getDescription(): string
    {
        return 'Create the rd_redirects table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::REDIRECTS_TABLE)) {
            return;
        }

        $table = $schema->createTable(self::REDIRECTS_TABLE);

        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('enabled', Types::BOOLEAN);
        $table->addColumn('status_code', Types::INTEGER);
        $table->addColumn('source', Types::STRING, ['length' => 191]);
        $table->addColumn('source_host', Types::STRING, ['length' => 191, 'notnull' => false]);
        $table->addColumn('target', Types::STRING, ['length' => 255]);
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('changed', Types::DATETIME_IMMUTABLE);
        $table->addColumn('creator_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('changer_id', Types::INTEGER, ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['source', 'source_host'], self::REDIRECTS_TABLE . '_source_unique');
        $table->addIndex(['creator_id']);
        $table->addIndex(['changer_id']);

        $table->addForeignKeyConstraint(self::USERS_TABLE, ['creator_id'], ['id'], ['onDelete' => 'SET NULL']);
        $table->addForeignKeyConstraint(self::USERS_TABLE, ['changer_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::REDIRECTS_TABLE)) {
            $schema->dropTable(self::REDIRECTS_TABLE);
        }
    }
}
