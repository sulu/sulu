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
    private const MEDIA_TABLE = 'me_media';

    public function getDescription(): string
    {
        return 'Add classification column with index to me_media';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::MEDIA_TABLE)) {
            return;
        }

        $media = $schema->getTable(self::MEDIA_TABLE);

        if (!$media->hasColumn('classification')) {
            $media->addColumn('classification', Types::STRING, [
                'length' => 191,
                'notnull' => false,
            ]);
        }

        if (!$this->hasClassificationIndex($media)) {
            // unnamed, so that Doctrine generates the same name as the entity mapping does
            $media->addIndex(['classification']);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable(self::MEDIA_TABLE)) {
            return;
        }

        $media = $schema->getTable(self::MEDIA_TABLE);

        foreach ($media->getIndexes() as $index) {
            if (!$index->isPrimary() && $index->spansColumns(['classification'])) {
                $media->dropIndex($index->getName());
            }
        }

        if ($media->hasColumn('classification')) {
            $media->dropColumn('classification');
        }
    }

    private function hasClassificationIndex(Table $media): bool
    {
        foreach ($media->getIndexes() as $index) {
            if ($index->spansColumns(['classification'])) {
                return true;
            }
        }

        return false;
    }
}
