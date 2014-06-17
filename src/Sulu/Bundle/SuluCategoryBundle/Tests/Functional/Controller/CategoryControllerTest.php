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
use Sulu\Bundle\CategoryBundle\Entity\CategoryMeta;
use Sulu\Bundle\CategoryBundle\Entity\CategoryName;
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

        /* First Category
        -------------------------------------*/
        $category = new Category();
        $category->setCreated(new \DateTime());
        $category->setChanged(new \DateTime());

        // name for first category
        $categoryName = new CategoryName();
        $categoryName->setLocale('en');
        $categoryName->setName('First Category');
        $categoryName->setCategory($category);
        $category->addName($categoryName);

        // meta for first category
        $categoryMeta = new CategoryMeta();
        $categoryMeta->setLocale('en');
        $categoryMeta->setKey('description');
        $categoryMeta->setValue('Description of Category');
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        self::$em->persist($category);

        /* Second Category
        -------------------------------------*/
        $category2 = new Category();
        $category2->setCreated(new \DateTime());
        $category2->setChanged(new \DateTime());

        // name for second category
        $categoryName2 = new CategoryName();
        $categoryName2->setLocale('de');
        $categoryName2->setName('Second Category');
        $categoryName2->setCategory($category);
        $category2->addName($categoryName2);

        // meta for second category
        $categoryMeta2 = new CategoryMeta();
        $categoryMeta2->setLocale('de');
        $categoryMeta2->setKey('description');
        $categoryMeta2->setValue('Description of second Category');
        $categoryMeta2->setCategory($category2);
        $category2->addMeta($categoryMeta2);

        // meta without locale for second category
        $categoryMeta3 = new CategoryMeta();
        $categoryMeta3->setKey('noLocaleKey');
        $categoryMeta3->setValue('noLocaleValue');
        $categoryMeta3->setCategory($category2);
        $category2->addMeta($categoryMeta3);

        self::$em->persist($category2);

        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryName'),
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
