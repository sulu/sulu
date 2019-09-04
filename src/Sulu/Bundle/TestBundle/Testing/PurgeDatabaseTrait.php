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
        if (!class_exists(ORMPurger::class)) {
            throw new \RuntimeException(
                'The composer package "doctrine/data-fixtures" is required to purge the database'
            );
        }

        $entityManager = static::getEntityManager();
        $connection = $entityManager->getConnection();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate('SET foreign_key_checks = 0;');
        }

        $purger = new ORMPurger();
        $executor = new ORMExecutor($entityManager, $purger);
        $referenceRepository = new ProxyReferenceRepository($entityManager);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();

        if ($connection->getDriver() instanceof \Doctrine\DBAL\Driver\PDOMySql\Driver) {
            $connection->executeUpdate('SET foreign_key_checks = 1;');
        }
    }

    protected static function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    abstract public static function getContainer(): ContainerInterface;
}
