<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;
use PHPCR\Migrations\MigratorFactory;
use PHPCR\Migrations\VersionStorage;

/**
 * Initialize the migrations when Sulu is built.
 *
 * After a repository has been intialized all migrations should be added to the
 * repository.
 */
class PhpcrMigrationsBuilder extends SuluBuilder
{
    /**
     * @var MigratorFactory
     */
    private $migratorFactory;

    /**
     * @var VersionStorage
     */
    private $versionStorage;

    public function __construct(MigratorFactory $migratorFactory, VersionStorage $versionStorage)
    {
        $this->migratorFactory = $migratorFactory;
        $this->versionStorage = $versionStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'phpcr_migrations';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['phpcr'];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $migrator = $this->migratorFactory->getMigrator();

        if (false === $this->versionStorage->hasVersioningNode()) {
            $migrator->initialize();
        }
    }
}
