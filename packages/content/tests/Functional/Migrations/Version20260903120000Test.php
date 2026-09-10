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

namespace Sulu\Content\Tests\Functional\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\NullLogger;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Migrations\Version20260903120000;

class Version20260903120000Test extends SuluTestCase
{
    private const TABLES = [
        'ct_workflow_transition_requests',
        'ct_workflow_transition_request_checks',
        'ct_workflow_transition_request_approvals',
    ];

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
        $this->connection = self::getEntityManager()->getConnection();
    }

    protected function tearDown(): void
    {
        // leave the schema migrated, so a failing assertion does not break the rest of the suite
        $this->runMigration('up');

        parent::tearDown();
    }

    public function testDownDropsTheTablesAndUpCreatesThemAsTheMappingDescribesThem(): void
    {
        $this->runMigration('down');

        foreach (self::TABLES as $tableName) {
            self::assertFalse($this->hasTable($tableName), $tableName);
        }

        $this->runMigration('up');

        foreach (self::TABLES as $tableName) {
            self::assertTrue($this->hasTable($tableName), $tableName);
        }

        self::assertSame(
            [],
            $this->pendingSchemaUpdates(),
            'The migration must create the tables exactly as the Doctrine mapping does.',
        );
    }

    /**
     * @param 'down'|'up' $direction
     */
    private function runMigration(string $direction): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = $schemaManager->introspectSchema();

        $migration = new Version20260903120000($this->connection, new NullLogger());
        $migration->$direction($toSchema);

        $platform = $this->connection->getDatabasePlatform();
        $schemaDiff = $schemaManager->createComparator()->compareSchemas($fromSchema, $toSchema);

        foreach ($platform->getAlterSchemaSQL($schemaDiff) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    private function hasTable(string $tableName): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$tableName]);
    }

    /**
     * @return list<string>
     */
    private function pendingSchemaUpdates(): array
    {
        $entityManager = self::getEntityManager();
        $schemaTool = new SchemaTool($entityManager);

        $statements = $schemaTool->getUpdateSchemaSql($entityManager->getMetadataFactory()->getAllMetadata());

        return \array_values(\array_filter(
            $statements,
            static fn (string $statement) => \str_contains($statement, 'ct_workflow_transition_request'),
        ));
    }
}
