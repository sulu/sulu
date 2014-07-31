<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\ContactBundle\Entity\AccountCategory;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class AccountCategoryControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;


    public function setUp()
    {
        $this->setUpSchema();

        $category = new AccountCategory();
        $category->setCategory('Hauptsitz');

        $category2 = new AccountCategory();
        $category2->setCategory('Nebensitz');

        self::$em->persist($category);
        self::$em->persist($category2);

        self::$em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser'),
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\AccountCategory'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
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

    public function testGet()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            'api/account/categories/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Hauptsitz', $response->category);
        $this->assertEquals(1, $response->id);
    }

    public function testGetAll()
    {
        $this->checkAssertionsForOriginalState();
    }

    public function testPost()
    {
        $client = $this->createTestClient();

        $client->request(
            'POST',
            'api/account/categories',
            array(
                'category' => 'Nebensitz 2',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Nebensitz 2', $response->category);
        $this->assertEquals(3, $response->id);

        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Hauptsitz', $response2->_embedded->accountCategories[0]->category);
        $this->assertEquals(1, $response2->_embedded->accountCategories[0]->id);

        $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[1]->category);
        $this->assertEquals(2, $response2->_embedded->accountCategories[1]->id);

        $this->assertEquals('Nebensitz 2', $response2->_embedded->accountCategories[2]->category);
        $this->assertEquals(3, $response2->_embedded->accountCategories[2]->id);

    }

    public function testPostNonUniqueName()
    {
        $this->setExpectedException('Doctrine\DBAL\DBALException');

        $client = $this->createTestClient();
        $client->request(
            'POST',
            'api/account/categories',
            array(
                'category' => 'Hauptsitz',
            )
        );

        $this->assertEquals(500, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();

    }

    public function testPostInvalidCategoryName()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            'api/account/categories',
            array(
                'category',
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testPostEmptyCategoryName()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            'api/account/categories',
            array(
                'category' => '',
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testPut()
    {
        $client = $this->createTestClient();
        $client->request(
            'PUT',
            'api/accounts/1/category',
            array(
                'category' => 'Nebensitz 3'
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Nebensitz 3', $response->category);
        $this->assertEquals(1, $response->id);

        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[0]->category);
        $this->assertEquals(2, $response2->_embedded->accountCategories[0]->id);

        $this->assertEquals('Nebensitz 3', $response2->_embedded->accountCategories[1]->category);
        $this->assertEquals(1, $response2->_embedded->accountCategories[1]->id);

    }

    public function testPutInvalidId()
    {
        $client = $this->createTestClient();
        $client->request(
            'PUT',
            'api/accounts/100/category',
            array(
                'category' => 'Nebensitz 3'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testDelete()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', 'api/account/categories/1');

        $this->assertEquals('204', $client->getResponse()->getStatusCode());
        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(1, count($response2->_embedded->accountCategories));
    }

    public function testDeleteInvalidId()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', 'api/account/categories/1000');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client2 = $this->createTestClient();

        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(2, count($response2->_embedded->accountCategories));
    }

    public function testPatch()
    {

        $client = $this->createTestClient();
        $client->request(
            'PATCH',
            'api/account/categories',
            array(
                array(
                    'id' => 1,
                    'category' => 'Changed Hauptsitz',
                ),
                array(
                    'category' => 'Neuer Nebensitz',
                )
            )
        );

        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Changed Hauptsitz', $response[0]->category);
        $this->assertEquals(1, $response[0]->id);

        $this->assertEquals('Neuer Nebensitz', $response[1]->category);
        $this->assertEquals(3, $response[1]->id);

        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(3, count($response2->_embedded->accountCategories));

        if($response2->_embedded->accountCategories[0]->category == 'Changed Hauptsitz') {
            $this->assertEquals('Changed Hauptsitz', $response2->_embedded->accountCategories[0]->category);
            $this->assertEquals(1, $response2->_embedded->accountCategories[0]->id);

            $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[1]->category);
            $this->assertEquals(2, $response2->_embedded->accountCategories[1]->id);

            $this->assertEquals('Neuer Nebensitz', $response2->_embedded->accountCategories[2]->category);
            $this->assertEquals(3, $response2->_embedded->accountCategories[2]->id);
        } else {
            $this->assertEquals('Changed Hauptsitz', $response2->_embedded->accountCategories[2]->category);
            $this->assertEquals(1, $response2->_embedded->accountCategories[2]->id);

            $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[0]->category);
            $this->assertEquals(2, $response2->_embedded->accountCategories[0]->id);

            $this->assertEquals('Neuer Nebensitz', $response2->_embedded->accountCategories[1]->category);
            $this->assertEquals(3, $response2->_embedded->accountCategories[1]->id);
        }
    }

    public function testPatchInvalidId()
    {
        $client = $this->createTestClient();
        $client->request(
            'PATCH',
            'api/account/categories',
            array(
                array(
                    'id' => 1,
                    'category' => 'Changed Hauptsitz',
                ),
                array(
                    'id' => 1000,
                    'category' => 'Neuer Nebensitz',
                )
            )
        );

        $this->assertEquals('404', $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testPatchInvalidCategoryName()
    {
        $client = $this->createTestClient();
        $client->request(
            'PATCH',
            'api/account/categories',
            array(
                array(
                    'id' => 1,
                    'category' => 'Changed Hauptsitz',
                ),
                array(
                    'category',
                )
            )
        );

        $this->assertEquals('400', $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function checkAssertionsForOriginalState()
    {
        $client2 = $this->createTestClient();

        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Hauptsitz', $response2->_embedded->accountCategories[0]->category);
        $this->assertEquals(1, $response2->_embedded->accountCategories[0]->id);

        $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[1]->category);
        $this->assertEquals(2, $response2->_embedded->accountCategories[1]->id);
    }
}
