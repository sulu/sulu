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
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109000000 extends AbstractMigration
{
    private const FILE_VERSION_TABLE = 'me_file_versions';

    public function getDescription(): string
    {
        return 'Add classification column with index to me_file_versions';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::FILE_VERSION_TABLE)) {
            return;
        }

        $fileVersion = $schema->getTable(self::FILE_VERSION_TABLE);

        if (!$fileVersion->hasColumn('classification')) {
            $fileVersion->addColumn('classification', Types::STRING, [
                'length' => 191,
                'notnull' => false,
            ]);
        }

        if (!$this->hasClassificationIndex($fileVersion)) {
            // unnamed, so that Doctrine generates the same name as the entity mapping does
            $fileVersion->addIndex(['classification']);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::FILE_VERSION_TABLE)) {
            return;
        }

        $fileVersion = $schema->getTable(self::FILE_VERSION_TABLE);

        foreach ($fileVersion->getIndexes() as $index) {
            if (!$index->isPrimary() && $index->spansColumns(['classification'])) {
                $fileVersion->dropIndex($index->getName());
            }
        }

        if ($fileVersion->hasColumn('classification')) {
            $fileVersion->dropColumn('classification');
        }
    }

    private function hasClassificationIndex(Table $fileVersion): bool
    {
        foreach ($fileVersion->getIndexes() as $index) {
            if ($index->spansColumns(['classification'])) {
                return true;
            }
        }

        return false;
    }
}
