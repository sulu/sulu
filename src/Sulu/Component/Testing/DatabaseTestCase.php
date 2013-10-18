<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Testing;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseTestCase extends WebTestCase
{
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
    }

    public static function tearDownAfterClass()
    {
        self::$em->close();
    }
}
