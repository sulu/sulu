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

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Migrations\Version20261005000000;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class Version20261005000000Test extends SuluTestCase
{
    private const TABLE = 'me_file_version_content_languages';

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

    public function testUpCreatesTheMissingTable(): void
    {
        $this->dropTable();
        self::assertFalse($this->hasTable(self::TABLE));

        $this->runMigration('up');

        self::assertTrue($this->hasTable(self::TABLE));
        self::assertTrue($this->hasColumn(self::TABLE, 'locale'));
        self::assertTrue($this->hasColumn(self::TABLE, 'idFileVersions'));
    }

    public function testUpMatchesTheOrmMapping(): void
    {
        $this->dropTable();
        $this->runMigration('up');

        $entityManager = self::getEntityManager();
        $ormSchema = (new SchemaTool($entityManager))->getSchemaFromMetadata([
            $entityManager->getClassMetadata(FileVersion::class),
            $entityManager->getClassMetadata(FileVersionContentLanguage::class),
        ]);
        $schemaManager = $this->connection->createSchemaManager();
        $tableDiff = $schemaManager->createComparator()->compareTables(
            $schemaManager->introspectTable(self::TABLE),
            $ormSchema->getTable(self::TABLE)
        );

        self::assertSame(
            [],
            $this->connection->getDatabasePlatform()->getAlterTableSQL($tableDiff),
            'The migrated table must not differ from the ORM mapping.'
        );
    }

    public function testUpKeepsAnExistingTableWithItsRows(): void
    {
        $this->connection->insert(self::TABLE, ['locale' => 'de']);

        $this->runMigration('up');

        self::assertSame(
            ['de'],
            $this->connection->fetchFirstColumn('SELECT locale FROM ' . self::TABLE),
            'A table that exists since 2.x must keep its rows.'
        );
    }

    public function testDownKeepsTheTable(): void
    {
        $this->runMigration('down');

        self::assertTrue($this->hasTable(self::TABLE));
    }

    private function dropTable(): void
    {
        $this->connection->createSchemaManager()->dropTable(self::TABLE);
    }

    /**
     * @param 'down'|'up' $direction
     */
    private function runMigration(string $direction): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = $schemaManager->introspectSchema();

        $migration = new Version20261005000000($this->connection, new NullLogger());
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

    private function hasColumn(string $tableName, string $columnName): bool
    {
        return $this->connection->createSchemaManager()->introspectTable($tableName)->hasColumn($columnName);
    }
}
