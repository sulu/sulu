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
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Migrations\Version20260820120000;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class Version20260820120000Test extends SuluTestCase
{
    private const FILE_VERSIONS_COLUMNS = ['origin', 'aiDisclosureDisabled', 'aiDisclosureIconVariant'];

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

    public function testDownRemovesColumnsAndUpAddsThemBack(): void
    {
        $this->runMigration('down');

        foreach (self::FILE_VERSIONS_COLUMNS as $columnName) {
            self::assertFalse($this->hasColumn('me_file_versions', $columnName), $columnName);
        }
        self::assertFalse($this->hasColumn('me_file_version_meta', 'aiDisclosureText'));

        $this->runMigration('up');

        foreach (self::FILE_VERSIONS_COLUMNS as $columnName) {
            self::assertTrue($this->hasColumn('me_file_versions', $columnName), $columnName);
        }
        self::assertTrue($this->hasColumn('me_file_version_meta', 'aiDisclosureText'));
    }

    public function testUpBackfillsExistingRowsWithTheDefaults(): void
    {
        [$fileVersionId, $fileVersionMetaId] = $this->createFileVersion();

        // the rows survive dropping and re-adding the columns, which is the state of an
        // installation that already has media and upgrades to the new columns
        $this->runMigration('down');
        $this->runMigration('up');

        $fileVersion = $this->fetchRow('me_file_versions', $fileVersionId);
        self::assertSame('unknown', $fileVersion['origin']);
        self::assertFalse((bool) $fileVersion['aiDisclosureDisabled']);
        self::assertSame('auto', $fileVersion['aiDisclosureIconVariant']);

        $fileVersionMeta = $this->fetchRow('me_file_version_meta', $fileVersionMetaId);
        self::assertNull($fileVersionMeta['aiDisclosureText']);
    }

    public function testUpIsIdempotent(): void
    {
        $this->runMigration('up');
        $this->runMigration('up');

        foreach (self::FILE_VERSIONS_COLUMNS as $columnName) {
            self::assertTrue($this->hasColumn('me_file_versions', $columnName), $columnName);
        }
        self::assertTrue($this->hasColumn('me_file_version_meta', 'aiDisclosureText'));
    }

    /**
     * @param 'down'|'up' $direction
     */
    private function runMigration(string $direction): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = $schemaManager->introspectSchema();

        $migration = new Version20260820120000($this->connection, new NullLogger());
        $migration->$direction($toSchema);

        $platform = $this->connection->getDatabasePlatform();
        $schemaDiff = $schemaManager->createComparator()->compareSchemas($fromSchema, $toSchema);

        foreach ($platform->getAlterSchemaSQL($schemaDiff) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    private function hasColumn(string $tableName, string $columnName): bool
    {
        return $this->connection->createSchemaManager()->introspectTable($tableName)->hasColumn($columnName);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRow(string $tableName, int $id): array
    {
        $row = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($tableName)
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();
        self::assertIsArray($row);

        return $row;
    }

    /**
     * @return array{int, int} file version id and file version meta id
     */
    private function createFileVersion(): array
    {
        $entityManager = self::getEntityManager();

        $fileVersion = new FileVersion();
        $fileVersion->setName('migration-test.jpg');
        $fileVersion->setVersion(1);
        $fileVersion->setSize(1024);
        $fileVersion->setMimeType('image/jpeg');
        $fileVersion->setCreated(new \DateTimeImmutable('2026-08-20'));
        $fileVersion->setChanged(new \DateTimeImmutable('2026-08-20'));
        // non-default values, so the assertions prove the migration applied the defaults
        $fileVersion->setOrigin('ai_generated');
        $fileVersion->setAiDisclosureDisabled(true);
        $fileVersion->setAiDisclosureIconVariant('dark');

        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale('en');
        $fileVersionMeta->setTitle('Migration Test');
        $fileVersionMeta->setAiDisclosureText('Generated with AI');
        $fileVersionMeta->setFileVersion($fileVersion);
        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $entityManager->persist($fileVersion);
        $entityManager->persist($fileVersionMeta);
        $entityManager->flush();

        return [(int) $fileVersion->getId(), (int) $fileVersionMeta->getId()];
    }
}
