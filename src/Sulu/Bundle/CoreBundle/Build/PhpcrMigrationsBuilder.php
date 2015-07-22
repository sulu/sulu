<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

/**
 * Initialize the migrations when Sulu is built.
 *
 * After a repository has been intialized all migrations should be added to the
 * repository.
 */
class PhpcrMigrationsBuilder extends SuluBuilder
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'phpcr_migrations';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return ['phpcr'];
    }

    /**
     * {@inheritDoc}
     */
    public function build()
    {
        $migrator = $this->container->get('phpcr_migrations.migrator_factory')->getMigrator();
        $versionStorage = $this->container->get('phpcr_migrations.version_storage');

        if (false === $versionStorage->hasVersioningNode()) {
            $migrator->initialize();
        }
    }
}
