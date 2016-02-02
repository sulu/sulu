<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseTestCase extends WebTestCase
{
    /**
     * @var array
     */
    protected static $userClasses;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected static $em;

    /**
     * @var SchemaTool
     */
    protected static $tool;

    public static function setUpBeforeClass()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        self::$em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        self::$tool = new SchemaTool(self::$em);

        self::$userClasses = [
            self::$em->getClassMetaData('Sulu\Bundle\TestBundle\Entity\TestContact'),
            self::$em->getClassMetaData('Sulu\Bundle\TestBundle\Entity\TestUser'),
        ];

        self::$tool->dropSchema(self::$userClasses);
        self::$tool->createSchema(self::$userClasses);

        static::$kernel->getContainer()->set(
            'sulu_security.user_repository',
            static::$kernel->getContainer()->get('test_user_provider')
        );
    }

    public static function tearDownAfterClass()
    {
        self::$tool->dropSchema(self::$userClasses);
        self::$em->close();
    }
}
