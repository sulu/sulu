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
use Doctrine\Migrations\AbstractMigration;

/**
 * MediaType denormalization, step 1 of 2: add me_media.type (nullable) and copy values from
 * the legacy me_media_types.name. Step 2 drops the legacy table/column and flips type to NOT NULL.
 */
final class Version20260429120400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'MediaType denormalization (1/2): add me_media.type and populate it from me_media_types.name';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('me_media')) {
            return;
        }

        $mediaTable = $schema->getTable('me_media');
        if (!$mediaTable->hasColumn('type')) {
            $mediaTable->addColumn('type', 'string', [
                'length' => 10,
                'notnull' => false,
            ]);
        }
    }

    public function postUp(Schema $schema): void
    {
        if (!$schema->hasTable('me_media') || !$schema->hasTable('me_media_types')) {
            return;
        }

        $mediaTable = $schema->getTable('me_media');
        if (!$mediaTable->hasColumn('idMediaTypes') || !$mediaTable->hasColumn('type')) {
            return;
        }

        $typeMap = $this->connection->createQueryBuilder()
            ->select('id', 'name')
            ->from('me_media_types')
            ->executeQuery()
            ->fetchAllKeyValue();

        foreach ($typeMap as $typeId => $name) {
            $this->connection->createQueryBuilder()
                ->update('me_media')
                ->set('type', ':name')
                ->where('idMediaTypes = :typeId')
                ->setParameter('name', $name)
                ->setParameter('typeId', $typeId)
                ->executeStatement();
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('me_media')) {
            return;
        }

        $mediaTable = $schema->getTable('me_media');
        if ($mediaTable->hasColumn('type')) {
            $mediaTable->dropColumn('type');
        }
    }
}
