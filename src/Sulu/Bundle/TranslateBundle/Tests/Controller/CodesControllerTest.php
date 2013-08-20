<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;

class CodesControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;
    /**
     * @var SchemaTool
     */
    protected static $tool;

    public function setUp()
    {
        $this->setUpSchema();

        // TODO create entities

        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Catalogue'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Code'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Location'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Package'),
            self::$em->getClassMetadata('Sulu\Bundle\TranslateBundle\Entity\Translation'),
        );

        self::$tool->createSchema(self::$entities);
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function testGetAll()
    {
        $client = static::createClient();
    }

    public function testGetAllSorted()
    {
        $client = static::createClient();
    }

    public function testGetAllPageSize()
    {
        $pageSize = 2;
        $client = static::createClient();
    }

    public function testGetAllFields()
    {
        $client = static::createClient();
    }

    public function testGetId()
    {
        $client = static::createClient();
    }

    public function testPost()
    {
        $client = static::createClient();
    }

    public function testPostWithoutName()
    {
        $client = static::createClient();
    }

    public function testPut()
    {
        $client = static::createClient();
    }

    public function testPutNotExisting()
    {
        $client = static::createClient();
    }

    // TODO test a few bad requests
}
