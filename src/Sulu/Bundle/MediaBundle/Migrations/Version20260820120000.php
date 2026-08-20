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

final class Version20260820120000 extends AbstractMigration
{
    private const FILE_VERSIONS_TABLE = 'me_file_versions';

    private const FILE_VERSION_META_TABLE = 'me_file_version_meta';

    public function getDescription(): string
    {
        return 'Add origin and AI disclosure columns to me_file_versions and me_file_version_meta';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable(self::FILE_VERSIONS_TABLE)) {
            $fileVersions = $schema->getTable(self::FILE_VERSIONS_TABLE);

            if (!$fileVersions->hasColumn('origin')) {
                $fileVersions->addColumn('origin', Types::STRING, [
                    'length' => 31,
                    'notnull' => true,
                    'default' => 'unknown',
                ]);
            }

            if (!$fileVersions->hasColumn('aiDisclosureDisabled')) {
                $fileVersions->addColumn('aiDisclosureDisabled', Types::BOOLEAN, [
                    'notnull' => true,
                    'default' => false,
                ]);
            }

            if (!$fileVersions->hasColumn('aiDisclosureIconVariant')) {
                $fileVersions->addColumn('aiDisclosureIconVariant', Types::STRING, [
                    'length' => 31,
                    'notnull' => true,
                    'default' => 'auto',
                ]);
            }
        }

        if ($schema->hasTable(self::FILE_VERSION_META_TABLE)) {
            $fileVersionMeta = $schema->getTable(self::FILE_VERSION_META_TABLE);

            if (!$fileVersionMeta->hasColumn('aiDisclosureText')) {
                $fileVersionMeta->addColumn('aiDisclosureText', Types::TEXT, [
                    'notnull' => false,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable(self::FILE_VERSIONS_TABLE)) {
            $fileVersions = $schema->getTable(self::FILE_VERSIONS_TABLE);

            foreach (['origin', 'aiDisclosureDisabled', 'aiDisclosureIconVariant'] as $columnName) {
                if ($fileVersions->hasColumn($columnName)) {
                    $fileVersions->dropColumn($columnName);
                }
            }
        }

        if ($schema->hasTable(self::FILE_VERSION_META_TABLE)) {
            $fileVersionMeta = $schema->getTable(self::FILE_VERSION_META_TABLE);

            if ($fileVersionMeta->hasColumn('aiDisclosureText')) {
                $fileVersionMeta->dropColumn('aiDisclosureText');
            }
        }
    }
}
