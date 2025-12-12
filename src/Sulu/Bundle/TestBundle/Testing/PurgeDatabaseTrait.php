<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait PurgeDatabaseTrait
{
    protected static function purgeDatabase(): void
    {
        if (!\class_exists(ORMPurger::class)) {
            throw new \RuntimeException(
                'The composer package "doctrine/data-fixtures" is required to purge the database'
            );
        }

        $entityManager = static::getEntityManager();
        $connection = $entityManager->getConnection();

        $isMysql = $connection->getDatabasePlatform() instanceof AbstractMySQLPlatform;
        $isPostgreSQL = $connection->getDatabasePlatform() instanceof PostgreSQLPlatform;

        if ($isMysql) {
            $connection->executeStatement('SET foreign_key_checks = 0;');
        }

        if ($isPostgreSQL) {
            $connection->executeStatement('SET session_replication_role = "replica";');
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($entityManager, $purger);
        $referenceRepository = new ProxyReferenceRepository($entityManager);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        if ($isMysql) {
            $connection->executeStatement('SET foreign_key_checks = 1;');
        }

        if ($isPostgreSQL) {
            $connection->executeStatement('SET session_replication_role = "origin";');
        }
    }

    protected static function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    abstract public static function getContainer(): ContainerInterface;
}
