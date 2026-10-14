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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928120000 extends AbstractMigration
{
    private const SETTINGS_TABLE = 'pa_webspace_settings';

    private const DIMENSION_CONTENTS_TABLE = 'pa_webspace_setting_contents';

    private const USERS_TABLE = 'se_users';

    public function getDescription(): string
    {
        return 'Create the webspace settings tables';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::SETTINGS_TABLE)) {
            $this->createSettingsTable($schema);
        }

        if (!$schema->hasTable(self::DIMENSION_CONTENTS_TABLE)) {
            $this->createDimensionContentsTable($schema);
        }
    }

    public function down(Schema $schema): void
    {
        foreach ([self::DIMENSION_CONTENTS_TABLE, self::SETTINGS_TABLE] as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
    }

    private function createSettingsTable(Schema $schema): void
    {
        $table = $schema->createTable(self::SETTINGS_TABLE);

        $table->addColumn('webspaceKey', Types::STRING, ['length' => 64]);
        $this->addAuditableColumns($table, 'IDX_BFD74061DBF11E1D', 'IDX_BFD7406130D07CD5', 'FK_BFD74061DBF11E1D', 'FK_BFD7406130D07CD5');

        $table->setPrimaryKey(['webspaceKey']);
    }

    private function createDimensionContentsTable(Schema $schema): void
    {
        $table = $schema->createTable(self::DIMENSION_CONTENTS_TABLE);
        $prefix = 'idx_' . self::DIMENSION_CONTENTS_TABLE . '_';

        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('webspaceKey', Types::STRING, ['length' => 64]);
        $table->addColumn('stage', Types::STRING, ['length' => 15]);
        $table->addColumn('locale', Types::STRING, ['length' => 15, 'notnull' => false]);
        $table->addColumn('ghostLocale', Types::STRING, ['length' => 15, 'notnull' => false]);
        $table->addColumn('availableLocales', Types::JSON, ['notnull' => false, 'platformOptions' => ['jsonb' => true]]);
        $table->addColumn('version', Types::INTEGER);
        $table->addColumn('templateKey', Types::STRING, ['length' => 64, 'notnull' => false]);
        $table->addColumn('templateData', Types::JSON, ['platformOptions' => ['jsonb' => true]]);
        $table->addColumn('workflowPlace', Types::STRING, ['length' => 31, 'notnull' => false]);
        $table->addColumn('workflowPublished', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $this->addAuditableColumns($table, 'IDX_15D906CEDBF11E1D', 'IDX_15D906CE30D07CD5', 'FK_15D906CEDBF11E1D', 'FK_15D906CE30D07CD5');

        $table->setPrimaryKey(['id']);
        $table->addIndex(['stage', 'locale'], $prefix . 'dimension');
        $table->addIndex(['locale'], $prefix . 'locale');
        $table->addIndex(['stage'], $prefix . 'stage');
        $table->addIndex(['version'], $prefix . 'version');
        $table->addIndex(['stage', 'version', 'locale'], $prefix . 'stage_version_locale');
        $table->addIndex(['webspaceKey', 'stage', 'version', 'locale', 'ghostLocale'], $prefix . 'resource_lookup');
        $table->addIndex(['templateKey'], $prefix . 'template_key');
        $table->addIndex(['webspaceKey', 'stage', 'version', 'locale', 'templateKey'], $prefix . 'resource_template_lookup');
        $table->addIndex(['workflowPlace'], $prefix . 'workflow_place');
        $table->addIndex(['workflowPublished'], $prefix . 'workflow_published');
        $table->addIndex(['webspaceKey'], 'IDX_15D906CE6B2C34DA');

        $table->addForeignKeyConstraint(
            self::SETTINGS_TABLE,
            ['webspaceKey'],
            ['webspaceKey'],
            ['onDelete' => 'CASCADE'],
            'FK_15D906CE6B2C34DA',
        );
    }

    private function addAuditableColumns(Table $table, string $creatorIndex, string $changerIndex, string $creatorForeignKey, string $changerForeignKey): void
    {
        $table->addColumn('created', Types::DATETIME_IMMUTABLE);
        $table->addColumn('changed', Types::DATETIME_IMMUTABLE);
        $table->addColumn('idUsersCreator', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('idUsersChanger', Types::INTEGER, ['notnull' => false]);

        $table->addIndex(['idUsersCreator'], $creatorIndex);
        $table->addIndex(['idUsersChanger'], $changerIndex);
        $table->addForeignKeyConstraint(self::USERS_TABLE, ['idUsersCreator'], ['id'], ['onDelete' => 'SET NULL'], $creatorForeignKey);
        $table->addForeignKeyConstraint(self::USERS_TABLE, ['idUsersChanger'], ['id'], ['onDelete' => 'SET NULL'], $changerForeignKey);
    }
}
