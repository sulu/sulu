<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use Doctrine\DBAL\Exception\ConnectionException;
use Webmozart\Assert\Assert;

/**
 * Builder for initializing the (relational) database.
 */
class DatabaseBuilder extends SuluBuilder
{
    public function getName()
    {
        return 'database';
    }

    public function getDependencies()
    {
        return [];
    }

    public function build()
    {
        $doctrine = $this->container->get('doctrine');
        Assert::isInstanceOf($doctrine, \Doctrine\Bundle\DoctrineBundle\Registry::class);
        $connection = $doctrine->getConnection();
        Assert::isInstanceOf($connection, \Doctrine\DBAL\Connection::class);

        try {
            $schemaManager = $connection->createSchemaManager();
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
