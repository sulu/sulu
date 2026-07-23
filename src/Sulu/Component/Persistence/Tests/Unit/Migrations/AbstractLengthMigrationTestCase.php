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

namespace Sulu\Component\Persistence\Tests\Unit\Migrations;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\SkipMigration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sulu\Component\Persistence\Migrations\AbstractLengthMigration;

abstract class AbstractLengthMigrationTestCase extends TestCase
{
    /**
     * @return class-string<AbstractLengthMigration>
     */
    abstract protected function getMigrationClass(): string;

    /**
     * @return array<string, array<string, int>> table name => column name => length
     */
    abstract protected function getExpectedWidenedLengths(): array;

    /**
     * @return array<string, array<string, int>> table name => column name => length
     */
    abstract protected function getExpectedLegacyLengths(): array;

    public function testUpWidensColumnLengths(): void
    {
        $schema = $this->createSchema($this->getExpectedLegacyLengths());

        $this->createMigration(false)->up($schema);

        $this->assertLengths($schema, $this->getExpectedWidenedLengths());
    }

    public function testUpRemainsPendingWhenLegacyLengthEnabled(): void
    {
        $schema = $this->createSchema($this->getExpectedLegacyLengths());

        $this->expectException(SkipMigration::class);

        $this->createMigration(true)->up($schema);
    }

    public function testDownRevertsColumnLengths(): void
    {
        $schema = $this->createSchema($this->getExpectedWidenedLengths());

        $this->createMigration(false)->down($schema);

        $this->assertLengths($schema, $this->getExpectedLegacyLengths());
    }

    public function testDownRemainsPendingWhenLegacyLengthEnabled(): void
    {
        $schema = $this->createSchema($this->getExpectedWidenedLengths());

        $this->expectException(SkipMigration::class);

        $this->createMigration(true)->down($schema);
    }

    public function testUpIsNoopWhenTableIsMissing(): void
    {
        $schema = new Schema();

        $this->createMigration(false)->up($schema);

        self::assertSame([], $schema->getTables());
    }

    public function testUpThrowsWhenLegacyLengthWasNeverSet(): void
    {
        $class = $this->getMigrationClass();
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $migration = new $class($connection, new NullLogger());

        $this->expectException(\LogicException::class);

        $migration->up(new Schema());
    }

    private function createMigration(bool $legacyLength): AbstractLengthMigration
    {
        $class = $this->getMigrationClass();
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $migration = new $class($connection, new NullLogger());
        $migration->setLegacyLength($legacyLength);

        return $migration;
    }

    /**
     * @param array<string, array<string, int>> $lengths
     */
    private function createSchema(array $lengths): Schema
    {
        $schema = new Schema();

        foreach ($lengths as $tableName => $columns) {
            $table = $schema->createTable($tableName);
            foreach ($columns as $columnName => $length) {
                $table->addColumn($columnName, Types::STRING, ['length' => $length]);
            }
        }

        return $schema;
    }

    /**
     * @param array<string, array<string, int>> $lengths
     */
    private function assertLengths(Schema $schema, array $lengths): void
    {
        foreach ($lengths as $tableName => $columns) {
            $table = $schema->getTable($tableName);
            foreach ($columns as $columnName => $length) {
                self::assertSame($length, $table->getColumn($columnName)->getLength(), \sprintf('%s.%s', $tableName, $columnName));
            }
        }
    }
}
