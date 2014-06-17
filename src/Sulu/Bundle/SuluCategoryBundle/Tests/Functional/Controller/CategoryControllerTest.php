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
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
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
        $categoryTrans = new CategoryTranslation();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('First Category');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

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
        $categoryTrans2 = new CategoryTranslation();
        $categoryTrans2->setLocale('de');
        $categoryTrans2->setTranslation('Second Category');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

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

        /* Third Category (child of first)
        -------------------------------------*/
        $category3 = new Category();
        $category3->setCreated(new \DateTime());
        $category3->setChanged(new \DateTime());
        $category3->setParent($category);

        // name for second category
        $categoryTrans3 = new CategoryTranslation();
        $categoryTrans3->setLocale('en');
        $categoryTrans3->setTranslation('Third Category');
        $categoryTrans3->setCategory($category3);
        $category3->addTranslation($categoryTrans3);

        // meta for second category
        $categoryMeta4 = new CategoryMeta();
        $categoryMeta4->setLocale('de');
        $categoryMeta4->setKey('another');
        $categoryMeta4->setValue('Description of third Category');
        $categoryMeta4->setCategory($category3);
        $category3->addMeta($categoryMeta4);

        self::$em->persist($category3);

        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\Category'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation'),
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

    private function createTestClient()
    {
        return $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    public function testGetById()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/categories/1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('First Category', $response->name);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals(1, $response->id);
        $this->assertEquals(1, count($response->meta));
        $this->assertEquals('description', $response->meta[0]->key);
        $this->assertEquals('Description of Category', $response->meta[0]->value);
    }

    public function testByIdNotExisting() {
        $client = $this->createTestClient();
        $client->request(
            'GET',
            '/api/categories/100'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    public function testCGet() {
        $client = $this->createTestClient();
        $client->request(
            'GET',
            '/api/categories'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, count($response->_embedded));
    }
}
