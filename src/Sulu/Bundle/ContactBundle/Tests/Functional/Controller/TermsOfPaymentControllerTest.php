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
use Sulu\Bundle\ContactBundle\Entity\TermsOfPayment;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class TermsOfPaymentControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;


    public function setUp()
    {
        $this->setUpSchema();

        $term1 = new TermsOfPayment();
        $term1->setTerms('Term 1');

        $term2 = new TermsOfPayment();
        $term2->setTerms('Term 2');

        self::$em->persist($term1);
        self::$em->persist($term2);

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
            self::$em->getClassMetadata('Sulu\Bundle\ContactBundle\Entity\TermsOfPayment'),
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
            '/api/termsofpayments/1'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Term 1', $response->terms);
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
            '/api/termsofpayments',
            array(
                'terms' => 'Term 3',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Term 3', $response->terms);
        $this->assertEquals(3, $response->id);

        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Term 1', $response2->_embedded->termsOfPayments[0]->terms);
        $this->assertEquals(1, $response2->_embedded->termsOfPayments[0]->id);

        $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[1]->terms);
        $this->assertEquals(2, $response2->_embedded->termsOfPayments[1]->id);

        $this->assertEquals('Term 3', $response2->_embedded->termsOfPayments[2]->terms);
        $this->assertEquals(3, $response2->_embedded->termsOfPayments[2]->id);

    }

    public function testPostNonUniqueName()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/termsofpayments',
            array(
                'terms' => 'Term 1',
            )
        );

        $this->assertEquals(500, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();

    }

    public function testPostInvalidCTermsName()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/termsofpayments',
            array(
                'terms',
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testPostEmptyTermsName()
    {
        $client = $this->createTestClient();
        $client->request(
            'POST',
            '/api/termsofpayments',
            array(
                'terms' => '',
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
            '/api/termsofpayments/1',
            array(
                'terms' => 'Term 1.1'
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Term 1.1', $response->terms);
        $this->assertEquals(1, $response->id);

        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        if ($response2->_embedded->termsOfPayments[1]->terms === 'Term 2') {
            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertEquals(2, $response2->_embedded->termsOfPayments[1]->id);

            $this->assertEquals('Term 1.1', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertEquals(1, $response2->_embedded->termsOfPayments[0]->id);
        } else {
            $this->assertEquals('Term 1.1', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertEquals(1, $response2->_embedded->termsOfPayments[1]->id);

            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertEquals(2, $response2->_embedded->termsOfPayments[0]->id);
        }
    }

    public function testPutInvalidId()
    {
        $client = $this->createTestClient();
        $client->request(
            'PUT',
            '/api/termsofpayments/100',
            array(
                'terms' => 'Term 3'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testDelete()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/termsofpayments/1');

        $this->assertEquals('204', $client->getResponse()->getStatusCode());
        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(1, count($response2->_embedded->termsOfPayments));
    }

    public function testDeleteInvalidId()
    {
        $client = $this->createTestClient();
        $client->request('DELETE', '/api/termsofpayments/1000');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client2 = $this->createTestClient();

        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(2, count($response2->_embedded->termsOfPayments));
    }

    public function testPatch()
    {

        $client = $this->createTestClient();
        $client->request(
            'PATCH',
            '/api/termsofpayments',
            array(
                array(
                    'id' => 1,
                    'terms' => 'Changed Term',
                ),
                array(
                    'terms' => 'Neuer Term',
                )
            )
        );

        $this->assertEquals('200', $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Changed Term', $response[0]->terms);
        $this->assertEquals(1, $response[0]->id);

        $this->assertEquals('Neuer Term', $response[1]->terms);
        $this->assertEquals(3, $response[1]->id);

        $client2 = $this->createTestClient();
        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(3, count($response2->_embedded->termsOfPayments));

        if($response2->_embedded->termsOfPayments[0]->terms == 'Changed Term') {
            $this->assertEquals('Changed Term', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertEquals(1, $response2->_embedded->termsOfPayments[0]->id);

            $this->assertEquals('Neuer Term', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertEquals(3, $response2->_embedded->termsOfPayments[1]->id);

            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[2]->terms);
            $this->assertEquals(2, $response2->_embedded->termsOfPayments[2]->id);
        } else {
            $this->assertEquals('Changed Term', $response2->_embedded->termsOfPayments[2]->terms);
            $this->assertEquals(1, $response2->_embedded->termsOfPayments[2]->id);

            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertEquals(2, $response2->_embedded->termsOfPayments[0]->id);

            $this->assertEquals('Neuer Term', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertEquals(3, $response2->_embedded->termsOfPayments[1]->id);
        }
    }

    public function testPatchInvalidId()
    {
        $client = $this->createTestClient();
        $client->request(
            'PATCH',
            '/api/termsofpayments',
            array(
                array(
                    'id' => 1,
                    'terms' => 'Changed Term',
                ),
                array(
                    'id' => 1000,
                    'terms' => 'Neuer Term',
                )
            )
        );

        $this->assertEquals('404', $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testPatchInvalidTermsName()
    {
        $client = $this->createTestClient();
        $client->request(
            'PATCH',
            '/api/termsofpayments',
            array(
                array(
                    'id' => 1,
                    'terms' => 'Changed Term',
                ),
                array(
                    'terms',
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
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(2, count($response2->_embedded->termsOfPayments));

        $this->assertEquals('Term 1', $response2->_embedded->termsOfPayments[0]->terms);
        $this->assertEquals(1, $response2->_embedded->termsOfPayments[0]->id);

        $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[1]->terms);
        $this->assertEquals(2, $response2->_embedded->termsOfPayments[1]->id);
    }

}
