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

use Doctrine\DBAL\Exception\ConnectionException;

/**
 * Builder for initializing the (relational) database.
 */
class DatabaseBuilder extends SuluBuilder
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $doctrine = $this->container->get('doctrine');
        $connection = $databaseExists = $doctrine->getConnection();

        try {
            $schemaManager = $connection->getSchemaManager();
            $databaseExists = true;
            $schemaManager->listDatabases();
        } catch (ConnectionException $e) {
            $databaseExists = false;
        }

        if ($this->input->getOption('destroy')) {
            if ($databaseExists) {
                $this->execCommand('Dropping the database', 'doctrine:database:drop', [
                    '--force' => true,
                ]);
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

        $this->execCommand('Updating the schema', 'doctrine:schema:update', ['--force' => true]);
    }
}
