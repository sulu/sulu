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
use Doctrine\DBAL\Schema\Schema;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Migrations\Version20260429120400;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * The 3.x schema no longer ships me_media_types or me_media.idMediaTypes, so this test
 * fabricates them in setUp and tears them down again. Both the fabrication and the row
 * inserts use the DBAL Schema API and QueryBuilder, so the test runs on every supported
 * database.
 */
class Version20260429120400Test extends SuluTestCase
{
    private Connection $connection;
    private int $collectionId;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
        $this->connection = self::getEntityManager()->getConnection();

        $this->fabricateLegacySchema();
        $this->collectionId = $this->createCollectionId();
    }

    protected function tearDown(): void
    {
        $this->dropLegacySchema();
        parent::tearDown();
    }

    public function testPopulatesTypeFromLegacyMediaTypesJoin(): void
    {
        $imageTypeId = $this->insertMediaType(1, 'image');
        $documentTypeId = $this->insertMediaType(2, 'document');

        $imageId = $this->insertMediaWithLegacyType(101, $imageTypeId);
        $documentId = $this->insertMediaWithLegacyType(102, $documentTypeId);

        $this->runPostUp();

        self::assertSame('image', $this->fetchType($imageId));
        self::assertSame('document', $this->fetchType($documentId));
    }

    public function testIsNoOpWhenLegacyTableIsAlreadyGone(): void
    {
        $typeId = $this->insertMediaType(1, 'image');
        $mediaId = $this->insertMediaWithLegacyType(101, $typeId);

        $this->applySchemaChange(static function(Schema $schema): void {
            if ($schema->hasTable('me_media_types')) {
                $schema->dropTable('me_media_types');
            }
        });

        $this->runPostUp();

        self::assertSame('__seed__', $this->fetchType($mediaId));
    }

    public function testIsIdempotentOnRepeatedRuns(): void
    {
        $typeId = $this->insertMediaType(1, 'audio');
        $mediaId = $this->insertMediaWithLegacyType(101, $typeId);

        $this->runPostUp();
        $this->runPostUp();

        self::assertSame('audio', $this->fetchType($mediaId));
    }

    private function fabricateLegacySchema(): void
    {
        $this->applySchemaChange(static function(Schema $schema): void {
            if (!$schema->hasTable('me_media_types')) {
                $table = $schema->createTable('me_media_types');
                $table->addColumn('id', 'integer');
                $table->addColumn('name', 'string', ['length' => 50]);
                $table->setPrimaryKey(['id']);
            }

            $mediaTable = $schema->getTable('me_media');
            if (!$mediaTable->hasColumn('idMediaTypes')) {
                $mediaTable->addColumn('idMediaTypes', 'integer', ['notnull' => false]);
            }
        });
    }

    private function dropLegacySchema(): void
    {
        $this->applySchemaChange(static function(Schema $schema): void {
            if ($schema->hasTable('me_media_types')) {
                $schema->dropTable('me_media_types');
            }
            $mediaTable = $schema->getTable('me_media');
            if ($mediaTable->hasColumn('idMediaTypes')) {
                $mediaTable->dropColumn('idMediaTypes');
            }
        });
    }

    private function applySchemaChange(callable $modifier): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $fromSchema = $schemaManager->introspectSchema();
        $toSchema = clone $fromSchema;
        $modifier($toSchema);

        $diff = $schemaManager->createComparator()->compareSchemas($fromSchema, $toSchema);
        foreach ($this->connection->getDatabasePlatform()->getAlterSchemaSQL($diff) as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    private function runPostUp(): void
    {
        $migration = new Version20260429120400($this->connection, new NullLogger());
        $migration->postUp($this->connection->createSchemaManager()->introspectSchema());
    }

    private function createCollectionId(): int
    {
        $em = self::getEntityManager();

        $collectionType = new CollectionType();
        $collectionType->setName('Default');
        $collectionType->setDescription('Default Collection Type');

        $collection = new Collection();
        $collection->setType($collectionType);

        $meta = new CollectionMeta();
        $meta->setTitle('Test Collection');
        $meta->setDescription('Description');
        $meta->setLocale('en');
        $meta->setCollection($collection);
        $collection->addMeta($meta);

        $em->persist($collectionType);
        $em->persist($collection);
        $em->persist($meta);
        $em->flush();

        return (int) $collection->getId();
    }

    private function insertMediaType(int $id, string $name): int
    {
        $this->connection->createQueryBuilder()
            ->insert('me_media_types')
            ->values(['id' => ':id', 'name' => ':name'])
            ->setParameter('id', $id)
            ->setParameter('name', $name)
            ->executeStatement();

        return $id;
    }

    private function insertMediaWithLegacyType(int $mediaId, int $typeId): int
    {
        $this->connection->createQueryBuilder()
            ->insert('me_media')
            ->values([
                'id' => ':id',
                'idCollections' => ':collection',
                'idMediaTypes' => ':typeId',
                'type' => ':type',
            ])
            ->setParameter('id', $mediaId)
            ->setParameter('collection', $this->collectionId)
            ->setParameter('typeId', $typeId)
            ->setParameter('type', '__seed__')
            ->executeStatement();

        return $mediaId;
    }

    private function fetchType(int $mediaId): ?string
    {
        $value = $this->connection->createQueryBuilder()
            ->select('type')
            ->from('me_media')
            ->where('id = :id')
            ->setParameter('id', $mediaId)
            ->executeQuery()
            ->fetchOne();

        if (false === $value || null === $value) {
            return null;
        }

        self::assertIsString($value);

        return $value;
    }
}
