<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Doctrine\Tests\Unit;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Doctrine\ReferencesOption;

class ReferencesOptionTest extends TestCase
{
    private const ARRAY_ACCESS_DEPRECATION = 'https://github.com/doctrine/orm/pull/11211';

    protected function setUp(): void
    {
        Deprecation::enableTrackingDeprecations();
    }

    protected function tearDown(): void
    {
        Deprecation::disable();
    }

    public function testLoadClassMetadataDoesNotUseArrayAccessOnFieldMappings(): void
    {
        $classMetadata = $this->createClassMetadata(ReferencesOptionTestRegion::class, 'region');
        $classMetadata->mapField(['fieldName' => 'name', 'type' => 'string']);
        $before = Deprecation::getTriggeredDeprecations()[self::ARRAY_ACCESS_DEPRECATION] ?? 0;

        $this->createReferencesOption()->loadClassMetadata(
            new LoadClassMetadataEventArgs($classMetadata, $this->createStub(EntityManagerInterface::class)),
        );

        $this->assertSame($before, Deprecation::getTriggeredDeprecations()[self::ARRAY_ACCESS_DEPRECATION] ?? 0);
    }

    public function testPostGenerateSchemaTableAddsForeignKeyConstraint(): void
    {
        $classMetadata = $this->createClassMetadata(ReferencesOptionTestCity::class, 'city');
        $classMetadata->mapField([
            'fieldName' => 'regionId',
            'type' => 'integer',
            'options' => [
                'references' => [
                    'entity' => ReferencesOptionTestRegion::class,
                    'field' => 'id',
                    'onDelete' => 'CASCADE',
                ],
            ],
        ]);

        $referencesOption = $this->createReferencesOption();
        $referencesOption->loadClassMetadata(
            new LoadClassMetadataEventArgs($classMetadata, $this->createStub(EntityManagerInterface::class)),
        );

        $table = new Table('city');
        $table->addColumn('regionId', 'integer');

        $referencesOption->postGenerateSchemaTable(new GenerateSchemaTableEventArgs($classMetadata, new Schema(), $table));

        $foreignKeys = \array_values($table->getForeignKeys());
        $this->assertCount(1, $foreignKeys);
        $this->assertSame('region', $foreignKeys[0]->getForeignTableName());
        $this->assertSame(['regionId'], $foreignKeys[0]->getLocalColumns());
        $this->assertSame(['id'], $foreignKeys[0]->getForeignColumns());
        $this->assertSame('CASCADE', $foreignKeys[0]->getOption('onDelete'));
    }

    private function createReferencesOption(): ReferencesOption
    {
        $regionMetadata = $this->createClassMetadata(ReferencesOptionTestRegion::class, 'region');

        $objectManager = $this->createStub(ObjectManager::class);
        $objectManager->method('getClassMetadata')->willReturn($regionMetadata);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($objectManager);

        return new ReferencesOption($managerRegistry, []);
    }

    /**
     * @param class-string $className
     *
     * @return ClassMetadata<object>
     */
    private function createClassMetadata(string $className, string $tableName): ClassMetadata
    {
        $classMetadata = new ClassMetadata($className);
        $classMetadata->setPrimaryTable(['name' => $tableName]);
        $classMetadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);

        return $classMetadata;
    }
}

class ReferencesOptionTestRegion
{
    public int $id;

    public string $name;
}

class ReferencesOptionTestCity
{
    public int $id;

    public int $regionId;
}
