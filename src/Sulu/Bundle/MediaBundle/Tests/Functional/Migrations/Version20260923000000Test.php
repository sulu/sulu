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
use Sulu\Bundle\MediaBundle\Entity\FileVersionMediaLanguage;
use Sulu\Bundle\MediaBundle\Migrations\Version20260923000000;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class Version20260923000000Test extends SuluTestCase
{
    private const TABLE = 'me_file_version_media_languages';

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

    public function testDownDropsTableAndUpCreatesItBack(): void
    {
        self::assertTrue($this->hasTable(self::TABLE));

        $this->runMigration('down');
        self::assertFalse($this->hasTable(self::TABLE));

        $this->runMigration('up');
        self::assertTrue($this->hasTable(self::TABLE));
        self::assertTrue($this->hasColumn(self::TABLE, 'language'));
        self::assertTrue($this->hasColumn(self::TABLE, 'idFileVersions'));
    }

    public function testUpMatchesTheOrmMapping(): void
    {
        $this->runMigration('down');
        $this->runMigration('up');

        $entityManager = self::getEntityManager();
        $ormSchema = (new SchemaTool($entityManager))->getSchemaFromMetadata([
            $entityManager->getClassMetadata(FileVersion::class),
            $entityManager->getClassMetadata(FileVersionMediaLanguage::class),
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

    public function testUpIsIdempotent(): void
    {
        $this->runMigration('up');
        $this->runMigration('up');

        self::assertTrue($this->hasTable(self::TABLE));
    }

    /**
     * @param 'down'|'up' $direction
     */
    private function runMigration(string $direction): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = $schemaManager->introspectSchema();

        $migration = new Version20260923000000($this->connection, new NullLogger());
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
