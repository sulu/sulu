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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AccountCategoryControllerTest extends SuluTestCase
{
    private $category;
    private $category2;

    public function setUp()
    {
        $this->db('ORM')->purgeDatabase();
        $em = $this->db('ORM')->getOm();

        $this->category = new AccountCategory();
        $this->category->setCategory('Hauptsitz');

        $this->category2 = new AccountCategory();
        $this->category2->setCategory('Nebensitz');

        $em->persist($this->category);
        $em->persist($this->category2);

        $em->flush();
    }

    public function testGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            'api/account/categories/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Hauptsitz', $response->category);
        $this->assertNotNull($response->id);
    }

    public function testGetAll()
    {
        $this->checkAssertionsForOriginalState();
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

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
        $this->assertNotNull($response->id);

        $client2 = $this->createAuthenticatedClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Hauptsitz', $response2->_embedded->accountCategories[0]->category);
        $this->assertNotNull($response2->_embedded->accountCategories[0]->id);

        $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[1]->category);
        $this->assertNotNull($response2->_embedded->accountCategories[1]->id);

        $this->assertEquals('Nebensitz 2', $response2->_embedded->accountCategories[2]->category);
        $this->assertNotNull($response2->_embedded->accountCategories[2]->id);

    }

    public function testPostNonUniqueName()
    {
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
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
        $this->assertNotNull($response->id);

        $client2 = $this->createAuthenticatedClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[0]->category);
        $this->assertNotNull($response2->_embedded->accountCategories[0]->id);

        $this->assertEquals('Nebensitz 3', $response2->_embedded->accountCategories[1]->category);
        $this->assertNotNull($response2->_embedded->accountCategories[1]->id);

    }

    public function testPutInvalidId()
    {
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', 'api/account/categories/1');

        $this->assertEquals('204', $client->getResponse()->getStatusCode());
        $client2 = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', 'api/account/categories/1000');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client2 = $this->createAuthenticatedClient();

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

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            'api/account/categories',
            array(
                array(
                    'id' => $this->category->getId(),
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
        $this->assertNotNull($response[0]->id);

        $this->assertEquals('Neuer Nebensitz', $response[1]->category);
        $this->assertNotNull($response[1]->id);

        $client2 = $this->createAuthenticatedClient();
        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(3, count($response2->_embedded->accountCategories));

        if($response2->_embedded->accountCategories[0]->category == 'Changed Hauptsitz') {
            $this->assertEquals('Changed Hauptsitz', $response2->_embedded->accountCategories[0]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[0]->id);

            $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[1]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[1]->id);

            $this->assertEquals('Neuer Nebensitz', $response2->_embedded->accountCategories[2]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[2]->id);
        } else {
            $this->assertEquals('Changed Hauptsitz', $response2->_embedded->accountCategories[2]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[2]->id);

            $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[0]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[0]->id);

            $this->assertEquals('Neuer Nebensitz', $response2->_embedded->accountCategories[1]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[1]->id);
        }
    }

    public function testPatchInvalidId()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            'api/account/categories',
            array(
                array(
                    'id' => $this->category->getId(),
                    'category' => 'Changed Hauptsitz',
                ),
                array(
                    'id' => 10012381230,
                    'category' => 'Neuer Nebensitz',
                )
            )
        );

        $this->assertEquals('404', $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testPatchInvalidCategoryName()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            'api/account/categories',
            array(
                array(
                    'id' => $this->category->getId(),
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
        $client2 = $this->createAuthenticatedClient();

        $client2->request(
            'GET',
            'api/account/categories'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        if($response2->_embedded->accountCategories[0]->id == 1){
            $this->assertEquals('Hauptsitz', $response2->_embedded->accountCategories[0]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[0]->id);

            $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[1]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[1]->id);
        } else {
            $this->assertEquals('Hauptsitz', $response2->_embedded->accountCategories[0]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[0]->id);

            $this->assertEquals('Nebensitz', $response2->_embedded->accountCategories[1]->category);
            $this->assertNotNull($response2->_embedded->accountCategories[1]->id);
        }
    }
}
