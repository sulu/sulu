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

final class Version20260903120000 extends AbstractMigration
{
    private const REQUESTS_TABLE = 'ct_workflow_transition_requests';

    private const CHECKS_TABLE = 'ct_workflow_transition_request_checks';

    private const APPROVALS_TABLE = 'ct_workflow_transition_request_approvals';

    private const USERS_TABLE = 'se_users';

    public function getDescription(): string
    {
        return 'Create the workflow transition request, check and approval tables';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable(self::REQUESTS_TABLE)) {
            $this->createRequestsTable($schema);
        }

        if (!$schema->hasTable(self::CHECKS_TABLE)) {
            $this->createChecksTable($schema);
        }

        if (!$schema->hasTable(self::APPROVALS_TABLE)) {
            $this->createApprovalsTable($schema);
        }
    }

    public function down(Schema $schema): void
    {
        // both reference the requests, so they go first
        foreach ([self::APPROVALS_TABLE, self::CHECKS_TABLE, self::REQUESTS_TABLE] as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
    }

    private function createRequestsTable(Schema $schema): void
    {
        $table = $schema->createTable(self::REQUESTS_TABLE);

        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('resource_key', Types::STRING, ['length' => 191]);
        $table->addColumn('resource_id', Types::STRING, ['length' => 191]);
        $table->addColumn('locale', Types::STRING, ['length' => 15]);
        $table->addColumn('lifecycle', Types::STRING, ['length' => 16]);
        $table->addColumn('active_key', Types::STRING, ['length' => 191, 'notnull' => false]);
        $table->addColumn('workflow_name', Types::STRING, ['length' => 64]);
        $table->addColumn('required_human_approval_count', Types::INTEGER);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('changed_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('creator_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('changer_id', Types::INTEGER, ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['active_key']);
        $table->addIndex(['creator_id']);
        $table->addIndex(['changer_id']);
        $table->addIndex(['resource_key', 'resource_id', 'locale'], self::REQUESTS_TABLE . '_scope_idx');

        $table->addForeignKeyConstraint(self::USERS_TABLE, ['creator_id'], ['id'], ['onDelete' => 'SET NULL']);
        $table->addForeignKeyConstraint(self::USERS_TABLE, ['changer_id'], ['id'], ['onDelete' => 'SET NULL']);
    }

    private function createChecksTable(Schema $schema): void
    {
        $table = $schema->createTable(self::CHECKS_TABLE);

        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('workflow_transition_request_id', Types::STRING, ['length' => 36]);
        $table->addColumn('validator_key', Types::STRING, ['length' => 64]);
        $table->addColumn('blocking', Types::BOOLEAN);
        $table->addColumn('status', Types::STRING, ['length' => 16]);
        $table->addColumn('comment', Types::TEXT, ['notnull' => false]);
        $table->addColumn('decided_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['workflow_transition_request_id', 'validator_key'], 'ct_wtr_checks_validator_uq');

        $table->addForeignKeyConstraint(
            self::REQUESTS_TABLE,
            ['workflow_transition_request_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
        );
    }

    private function createApprovalsTable(Schema $schema): void
    {
        $table = $schema->createTable(self::APPROVALS_TABLE);

        $table->addColumn('id', Types::STRING, ['length' => 36]);
        $table->addColumn('workflow_transition_request_id', Types::STRING, ['length' => 36]);
        $table->addColumn('user_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('status', Types::STRING, ['length' => 16]);
        $table->addColumn('comment', Types::TEXT, ['notnull' => false]);
        $table->addColumn('decided_at', Types::DATETIME_IMMUTABLE);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id']);
        $table->addUniqueIndex(['workflow_transition_request_id', 'user_id'], 'ct_wtr_approvals_user_uq');

        $table->addForeignKeyConstraint(
            self::REQUESTS_TABLE,
            ['workflow_transition_request_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
        );
        $table->addForeignKeyConstraint(self::USERS_TABLE, ['user_id'], ['id'], ['onDelete' => 'SET NULL']);
    }
}
