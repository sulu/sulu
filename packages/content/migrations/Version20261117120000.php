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

namespace Sulu\Content\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261117120000 extends AbstractMigration
{
    private const REQUESTS_TABLE = 'ct_workflow_transition_requests';

    public function getDescription(): string
    {
        return 'Add the form data column to the workflow transition requests';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::REQUESTS_TABLE);

        if (!$table->hasColumn('data')) {
            $table->addColumn('data', Types::JSON, ['notnull' => false]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::REQUESTS_TABLE);

        if ($table->hasColumn('data')) {
            $table->dropColumn('data');
        }
    }
}
