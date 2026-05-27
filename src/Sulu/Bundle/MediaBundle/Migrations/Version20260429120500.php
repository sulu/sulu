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
 * MediaType denormalization, step 2 of 2: flip me_media.type to NOT NULL, index it, and drop
 * the legacy me_media.idMediaTypes column and me_media_types table.
 */
final class Version20260429120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'MediaType denormalization (2/2): drop me_media_types and me_media.idMediaTypes, set me_media.type NOT NULL';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('me_media')) {
            return;
        }

        $mediaTable = $schema->getTable('me_media');

        if ($mediaTable->hasColumn('type')) {
            $mediaTable->getColumn('type')->setNotnull(true);
            $mediaTable->addIndex(['type']);
        }

        if ($mediaTable->hasColumn('idMediaTypes')) {
            $mediaTable->dropColumn('idMediaTypes');
        }

        if ($schema->hasTable('me_media_types')) {
            $schema->dropTable('me_media_types');
        }
    }

    public function down(Schema $schema): void
    {
        // me_media_types rows are gone, the legacy idMediaTypes FK cannot be reconstructed.
    }
}
