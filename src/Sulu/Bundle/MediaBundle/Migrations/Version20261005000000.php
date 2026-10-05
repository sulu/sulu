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

final class Version20261005000000 extends AbstractMigration
{
    private const TABLE = 'me_file_version_content_languages';

    private const FILE_VERSIONS_TABLE = 'me_file_versions';

    public function getDescription(): string
    {
        return 'Add me_file_version_content_languages table storing the content locales of a file version, kept as is when it exists since 2.x';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::TABLE)) {
            return;
        }

        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('locale', Types::STRING, ['length' => 15]);
        $table->addColumn('idFileVersions', Types::INTEGER, ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint(
            self::FILE_VERSIONS_TABLE,
            ['idFileVersions'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    public function down(Schema $schema): void
    {
        // the table may predate this migration (2.x), so dropping it would delete data this migration never created
    }
}
