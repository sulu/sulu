<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Package;

class PackageControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $package = new Package();
        $package->setName('Sulu');
        $this->package1 = $package;
        $catalogue = new Catalogue();
        $catalogue->setPackage($package);
        $catalogue->setIsDefault(false);
        $catalogue->setLocale('EN');
        $this->catalogue1 = $catalogue;
        $this->em->persist($catalogue);
        $this->em->persist($package);

        $package = new Package();
        $package->setName('Global');
        $this->em->persist($package);

        $package = new Package();
        $package->setName('Portal');

        $this->em->persist($package);

        $this->em->flush();
    }

    public function testGetAll()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/packages');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);
        $this->assertEquals('Sulu', $response->_embedded->packages[0]->name);
        $this->assertEquals('Global', $response->_embedded->packages[1]->name);
        $this->assertEquals('Portal', $response->_embedded->packages[2]->name);
    }

    public function testGetAllSorted()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/packages?flat=true&sortBy=name&sortOrder=asc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Global', $response->_embedded->packages[0]->name);
        $this->assertEquals('Portal', $response->_embedded->packages[1]->name);
        $this->assertEquals('Sulu', $response->_embedded->packages[2]->name);

        $client->request('GET', '/api/packages?flat=true&sortBy=name&sortOrder=desc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Global', $response->_embedded->packages[2]->name);
        $this->assertEquals('Portal', $response->_embedded->packages[1]->name);
        $this->assertEquals('Sulu', $response->_embedded->packages[0]->name);

        $client->request('GET', '/api/packages?flat=true&sortBy=id&sortOrder=asc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->_embedded->packages[0]->name);
        $this->assertEquals('Global', $response->_embedded->packages[1]->name);
        $this->assertEquals('Portal', $response->_embedded->packages[2]->name);

        $client->request('GET', '/api/packages?flat=true&sortBy=id&sortOrder=desc');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->_embedded->packages[2]->name);
        $this->assertEquals('Global', $response->_embedded->packages[1]->name);
        $this->assertEquals('Portal', $response->_embedded->packages[0]->name);
    }

    public function testGetAllLimit()
    {
        $limit = 2;

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/packages?flat=true&limit=' . $limit);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($limit, count($response->_embedded->packages));
        $this->assertEquals(3, $response->total);
        $this->assertEquals('Sulu', $response->_embedded->packages[0]->name);
        $this->assertEquals('Global', $response->_embedded->packages[1]->name);

        $client->request('GET', '/api/packages?flat=true&limit=' . $limit . '&page=2');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, count($response->_embedded->packages)); // only 1 item remaining
        $this->assertEquals(3, $response->total); // only 1 item remaining
        $this->assertEquals('Portal', $response->_embedded->packages[0]->name);
    }

    public function testGetAllFields()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/packages?flat=true&fields=id,name');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->_embedded->packages[0]->name);
        $this->assertEquals('Global', $response->_embedded->packages[1]->name);
        $this->assertEquals('Portal', $response->_embedded->packages[2]->name);

        $client->request('GET', '/api/packages?flat=true&fields=name,id');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->_embedded->packages[0]->name);
        $this->assertNotNull($response->_embedded->packages[0]->id);
        $this->assertEquals('Global', $response->_embedded->packages[1]->name);
        $this->assertNotNull($response->_embedded->packages[1]->id);
        $this->assertEquals('Portal', $response->_embedded->packages[2]->name);
        $this->assertNotNull($response->_embedded->packages[2]->id);
    }

    public function testGetId()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/packages/' . $this->package1->getId());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotNull($response->id);
        $this->assertEquals('Sulu', $response->name);
        $this->assertEquals('EN', $response->catalogues[0]->locale);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/packages',
            [
                'name' => 'Portal',
                'catalogues' => [
                    ['locale' => 'EN'],
                    ['locale' => 'DE'],
                    ['locale' => 'ES'],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);

        $client->request('GET', '/api/packages/' . $response->id);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('EN', $response->catalogues[0]->locale);
        $this->assertEquals('DE', $response->catalogues[1]->locale);
        $this->assertEquals('ES', $response->catalogues[2]->locale);
    }

    public function testPostWithoutLanguages()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/packages',
            [
                'name' => 'Portal',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);

        $client->request(
            'GET',
            '/api/packages/' . $response->id
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
    }

    public function testPostWithoutName()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/packages',
            []
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/packages/' . $this->package1->getId(),
            [
                'name' => 'Portal',
                'catalogues' => [
                    ['id' => $this->catalogue1->getId(), 'locale' => 'DE'],
                    ['locale' => 'EN'],
                    ['locale' => 'ES'],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response->name);
        $this->assertEquals($this->package1->getId(), $response->id);
        $this->assertContains('DE', $response->catalogues[0]->locale);
        $this->assertContains('EN', $response->catalogues[1]->locale);
        $this->assertContains('ES', $response->catalogues[2]->locale);

        $client->request(
            'PUT',
            '/api/packages/' . $this->package1->getId(),
            [
                'name' => 'Portal',
                'catalogues' => [
                    ['id' => $response->catalogues[1]->id, 'locale' => 'ES'],
                    ['id' => $response->catalogues[2]->id, 'locale' => 'DE'],
                ],
            ]
        );

        $response1 = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal', $response1->name);
        $this->assertNotNull($response1->id);
        $this->assertEquals(2, count($response1->catalogues));
        $this->assertContains('ES', $response1->catalogues[0]->locale);
        $this->assertContains('DE', $response1->catalogues[1]->locale);
    }

    public function testPutWithoutLanguages()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/packages/' . $this->package1->getId(),
            [
                'name' => 'ASDF',
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('ASDF', $response->name);
        $this->assertEquals($this->package1->getId(), $response->id);

        $client->request(
            'GET',
            '/api/packages'
        );

        $response = json_decode($client->getResponse()->getContent());

        $names = [];
        foreach ($response->_embedded->packages as $package) {
            $names[] = $package->name;
        }

        $this->assertContains('ASDF', $names);
    }

    public function testPutNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/packages/10123',
            ['name' => 'Portal']
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPutNotExistingCatalogue()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/packages/' . $this->package1->getId(),
            [
                'name' => 'Portal',
                'catalogues' => [
                    ['id' => 123123, 'locale' => 'DE'],
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());

        $client->request(
            'GET',
            '/api/packages/' . $this->package1->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu', $response->name);
        $this->assertEquals('EN', $response->catalogues[0]->locale);
    }

    public function testDeleteById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/packages/' . $this->package1->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());
    }

    public function testDeleteByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/packages/4711');
        $this->assertHttpStatusCode(404, $client->getResponse());

        // there still have to be 3 packages
        $client->request('GET', '/api/packages');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, $response->total);
    }
}
