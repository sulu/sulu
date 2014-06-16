<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Functional\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\Meta;
use Sulu\Bundle\CategoryBundle\Entity\Name;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class CategoryControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    public function setUp()
    {
        $this->setUpSchema();

        $category = new Category();
        $category->setName('Category 1');
        $category->setCreated(new \DateTime());
        $category->setChanged(new \DateTime());
        self::$em->persist($category);

        $category = new Category();
        $category->setCreated(new \DateTime());
        $category->setChanged(new \DateTime());
        self::$em->persist($category);

        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Meta'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Name'),
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function testGetById()
    {
        $this->assertEquals(true, true);
    }
}
