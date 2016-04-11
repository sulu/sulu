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

class CatalogueControllerTest extends SuluTestCase
{
    /**
     * @var Package
     */
    private $package;

    /**
     * @Var Catalogue
     */
    private $catalogue;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $package = new Package();
        $package->setName('Sulu');
        $this->em->persist($package);
        $this->package = $package;

        $catalogue = new Catalogue();
        $catalogue->setPackage($package);
        $catalogue->setLocale('EN');
        $catalogue->setIsDefault(false);
        $this->em->persist($catalogue);
        $this->catalogue = $catalogue;

        $this->em->flush();
    }

    public function testGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/catalogues');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->_embedded->catalogues[0]->locale);
    }

    public function testGetByPackage()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/catalogues?package=' . $this->package->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->_embedded->catalogues[0]->locale);
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/catalogues/' . $this->catalogue->getId());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('EN', $response->locale);
    }

    public function testDeleteById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/catalogues/' . $this->catalogue->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/catalogues/' . $this->catalogue->getId());
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/catalogues/4711');
        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/catalogues');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    public function testListCatalogues()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/catalogues?flat=true&fields=id,locale&packageId=' . $this->package->getId());
        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals($this->catalogue->getId(), $response->_embedded->catalogues[0]->id);
        $this->assertEquals('EN', $response->_embedded->catalogues[0]->locale);
    }

    public function testListCataloguesNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/catalogues?flat=true&fields=id,locale&packageId=4711');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('0', $response->total);
    }

    // TODO more list tests
}
