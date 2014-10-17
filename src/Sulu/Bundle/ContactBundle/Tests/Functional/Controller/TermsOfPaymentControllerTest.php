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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TermsOfPaymentControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->initOrm();
    }

    private function initOrm()
    {
        $this->purgeDatabase();

        $term1 = new TermsOfPayment();
        $term1->setTerms('Term 1');

        $this->term1 = $term1;

        $term2 = new TermsOfPayment();
        $term2->setTerms('Term 2');

        $this->term2 = $term2;

        $this->em->persist($term1);
        $this->em->persist($term2);

        $this->em->flush();
    }

    public function testGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/termsofpayments/' . $this->term1->getId()
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Term 1', $response->terms);
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
            '/api/termsofpayments',
            array(
                'terms' => 'Term 3',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Term 3', $response->terms);
        $this->assertNotNull($response->id);

        $client2 = $this->createAuthenticatedClient();
        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals('Term 1', $response2->_embedded->termsOfPayments[0]->terms);
        $this->assertNotNull($response2->_embedded->termsOfPayments[0]->id);

        $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[1]->terms);
        $this->assertNotNull($response2->_embedded->termsOfPayments[1]->id);

        $this->assertEquals('Term 3', $response2->_embedded->termsOfPayments[2]->terms);
        $this->assertNotNull($response2->_embedded->termsOfPayments[2]->id);

    }

    public function testPostNonUniqueName()
    {
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/termsofpayments/' . $this->term1->getId(),
            array(
                'terms' => 'Term 1.1'
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('Term 1.1', $response->terms);
        $this->assertNotNull($response->id);

        $client2 = $this->createAuthenticatedClient();
        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        if ($response2->_embedded->termsOfPayments[1]->terms === 'Term 2') {
            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[1]->id);

            $this->assertEquals('Term 1.1', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[0]->id);
        } else {
            $this->assertEquals('Term 1.1', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[1]->id);

            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[0]->id);
        }
    }

    public function testPutInvalidId()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/termsofpayments/112312300',
            array(
                'terms' => 'Term 3'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->checkAssertionsForOriginalState();
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/termsofpayments/' . $this->term1->getId());

        $this->assertEquals('204', $client->getResponse()->getStatusCode());
        $client2 = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/termsofpayments/1123123123000');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client2 = $this->createAuthenticatedClient();

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

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/termsofpayments',
            array(
                array(
                    'id' => $this->term1->getId(),
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
        $this->assertNotNull($response[0]->id);

        $this->assertEquals('Neuer Term', $response[1]->terms);
        $this->assertNotNull($response[1]->id);

        $client2 = $this->createAuthenticatedClient();
        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(3, count($response2->_embedded->termsOfPayments));

        if($response2->_embedded->termsOfPayments[0]->terms == 'Changed Term') {
            $this->assertEquals('Changed Term', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[0]->id);

            $this->assertEquals('Neuer Term', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[1]->id);

            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[2]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[2]->id);
        } else {
            $this->assertEquals('Changed Term', $response2->_embedded->termsOfPayments[2]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[2]->id);

            $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[0]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[0]->id);

            $this->assertEquals('Neuer Term', $response2->_embedded->termsOfPayments[1]->terms);
            $this->assertNotNull($response2->_embedded->termsOfPayments[1]->id);
        }
    }

    public function testPatchInvalidId()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/termsofpayments',
            array(
                array(
                    'id' => $this->term1->getId(),
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
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/termsofpayments',
            array(
                array(
                    'id' => $this->term1->getId(),
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
        $client2 = $this->createAuthenticatedClient();

        $client2->request(
            'GET',
            '/api/termsofpayments'
        );

        $response2 = json_decode($client2->getResponse()->getContent());
        $this->assertEquals(200, $client2->getResponse()->getStatusCode());

        $this->assertEquals(2, count($response2->_embedded->termsOfPayments));

        $this->assertEquals('Term 1', $response2->_embedded->termsOfPayments[0]->terms);
        $this->assertNotNull($response2->_embedded->termsOfPayments[0]->id);

        $this->assertEquals('Term 2', $response2->_embedded->termsOfPayments[1]->terms);
        $this->assertNotNull($response2->_embedded->termsOfPayments[1]->id);
    }

}
