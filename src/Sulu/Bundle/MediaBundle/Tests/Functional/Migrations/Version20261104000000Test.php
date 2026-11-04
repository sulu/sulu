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
use Sulu\Bundle\MediaBundle\Migrations\Version20261104000000;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class Version20261104000000Test extends SuluTestCase
{
    private const TABLE = 'me_file_versions';

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

    public function testUpAddsTheMissingColumns(): void
    {
        $this->runMigration('down');
        self::assertFalse($this->hasColumn('validFrom'));
        self::assertFalse($this->hasColumn('validTo'));

        $this->runMigration('up');

        self::assertTrue($this->hasColumn('validFrom'));
        self::assertTrue($this->hasColumn('validTo'));
    }

    public function testUpMatchesTheOrmMapping(): void
    {
        $this->runMigration('down');
        $this->runMigration('up');

        $entityManager = self::getEntityManager();
        $ormSchema = (new SchemaTool($entityManager))->getSchemaFromMetadata([
            $entityManager->getClassMetadata(FileVersion::class),
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

    public function testUpKeepsExistingColumns(): void
    {
        $this->runMigration('up');

        self::assertTrue($this->hasColumn('validFrom'));
        self::assertTrue($this->hasColumn('validTo'));
    }

    public function testDownDropsTheColumns(): void
    {
        $this->runMigration('down');

        self::assertFalse($this->hasColumn('validFrom'));
        self::assertFalse($this->hasColumn('validTo'));
    }

    /**
     * @param 'down'|'up' $direction
     */
    private function runMigration(string $direction): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = $schemaManager->introspectSchema();

        $migration = new Version20261104000000($this->connection, new NullLogger());
        $migration->$direction($toSchema);

        $platform = $this->connection->getDatabasePlatform();
        $schemaDiff = $schemaManager->createComparator()->compareSchemas($fromSchema, $toSchema);

        foreach ($platform->getAlterSchemaSQL($schemaDiff) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    private function hasColumn(string $columnName): bool
    {
        return $this->connection->createSchemaManager()->introspectTable(self::TABLE)->hasColumn($columnName);
    }
}
