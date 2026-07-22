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

namespace Sulu\Component\Persistence\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Widens (or, on rollback, shrinks) column lengths for the pre-3.x -> 3.x length increase.
 *
 * Skipped entirely for installations that opted into `sulu_persistence.legacy_length`, since
 * their database schema is expected to stay at the pre-3.x column widths and their ORM mapping
 * is shrunk back down to match by {@see \Sulu\Component\Persistence\EventSubscriber\ORM\LegacyLengthSubscriber}.
 */
abstract class AbstractLengthMigration extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
        #[Autowire(param: 'sulu.persistence.legacy_length')]
        private readonly bool $legacyLength = false,
    ) {
        parent::__construct($connection, $logger);
    }

    /**
     * @return array<string, array<string, int>> table name => column name => length
     */
    abstract protected function getWidenedLengths(): array;

    /**
     * @return array<string, array<string, int>> table name => column name => length
     */
    abstract protected function getLegacyLengths(): array;

    final public function up(Schema $schema): void
    {
        if ($this->legacyLength) {
            return;
        }

        $this->changeLengths($schema, $this->getWidenedLengths());
    }

    final public function down(Schema $schema): void
    {
        if ($this->legacyLength) {
            return;
        }

        $this->changeLengths($schema, $this->getLegacyLengths());
    }

    /**
     * @param array<string, array<string, int>> $lengths
     */
    private function changeLengths(Schema $schema, array $lengths): void
    {
        foreach ($lengths as $tableName => $columns) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            $table = $schema->getTable($tableName);
            foreach ($columns as $columnName => $length) {
                if ($table->hasColumn($columnName)) {
                    $table->getColumn($columnName)->setLength($length);
                }
            }
        }
    }
}
