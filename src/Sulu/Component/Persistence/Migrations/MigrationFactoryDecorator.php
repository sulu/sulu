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

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;

/**
 * @internal
 *
 * Pushes the resolved `sulu_persistence.legacy_length` value onto freshly created
 * {@see AbstractLengthMigration} instances, since doctrine-migrations instantiates
 * migrations itself and has no notion of our container parameter otherwise.
 */
final class MigrationFactoryDecorator implements MigrationFactory
{
    public function __construct(
        private readonly MigrationFactory $inner,
        private readonly bool $legacyLength,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->inner->createVersion($migrationClassName);

        if ($migration instanceof AbstractLengthMigration) {
            $migration->setLegacyLength($this->legacyLength);
        }

        return $migration;
    }
}
