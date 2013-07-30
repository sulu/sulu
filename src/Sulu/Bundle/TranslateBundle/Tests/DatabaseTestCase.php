<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DatabaseTestCase extends WebTestCase {
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected static $em;

    public static function setUpBeforeClass()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        self::$em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public static function tearDownAfterClass()
    {
        self::$em->close();
    }
}