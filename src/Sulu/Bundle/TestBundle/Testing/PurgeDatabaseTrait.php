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

        $isMysql = 'mysql' === $connection->getDriver()->getDatabasePlatform()->getName();
        $isPostgreSQL = 'postgresql' === $connection->getDriver()->getDatabasePlatform()->getName();

        $executeDoctrineStatement = function(string $sql) use ($connection) {
            if (\method_exists($connection, 'executeStatement')) {
                $connection->executeStatement($sql);
            } else {
                // executeUpdate can be removed when upgrade to a doctrine/dbal 3
                $connection->executeUpdate($sql);
            }
        };

        if ($isMysql) {
            $executeDoctrineStatement('SET foreign_key_checks = 0;');
        }

        if ($isPostgreSQL) {
            $executeDoctrineStatement('SET session_replication_role = "replica";');
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($entityManager, $purger);
        $referenceRepository = new ProxyReferenceRepository($entityManager);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        if ($isMysql) {
            $executeDoctrineStatement('SET foreign_key_checks = 1;');
        }

        if ($isPostgreSQL) {
            $executeDoctrineStatement('SET session_replication_role = "origin";');
        }
    }

    protected static function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    abstract public static function getContainer(): ContainerInterface;
}
