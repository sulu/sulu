<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

/**
 * Builder for initializing the (relational) database.
 */
class DatabaseBuilder extends SuluBuilder
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'database';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function build()
    {
        $doctrine = $this->container->get('doctrine');
        $connection = $databaseExists = $doctrine->getConnection();
        $schemaManager = $connection->getSchemaManager();

        try {
            $databaseExists = true;
            $schemaManager->listDatabases();
        } catch (\Exception $e) {
            $databaseExists = false;
        }

        if ($this->input->getOption('destroy')) {
            if ($databaseExists) {
                $this->execCommand('Dropping the database', 'doctrine:database:drop', array(
                    '--force' => true,
                ));
            }
            $this->execCommand('Creating the database', 'doctrine:database:create');

            // avoid "Invalid catalog name: 1046 No database selected" by reconnecting
            $doctrine->getConnection()->close();
            $doctrine->getConnection()->connect();

            $this->execCommand('Creating the schema', 'doctrine:schema:create');

            return;
        }

        if (!$databaseExists) {
            $this->execCommand('Creating database', 'doctrine:database:create');
            $this->execCommand('Creating the schema', 'doctrine:schema:create');
        }

        $this->execCommand('Updating the schema', 'doctrine:schema:update', array('--force' => true));
    }
}
