<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->getContainer()->get('sulu_test.doctrine.orm.default_entity_manager');
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());
    }

    public function testGetConfig()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/config');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('sulu_admin', $response);
        $this->assertObjectHasAttribute('navigation', $response->sulu_admin);
        $this->assertObjectHasAttribute('resourceMetadataEndpoints', $response->sulu_admin);
        $this->assertObjectHasAttribute('routes', $response->sulu_admin);
        $this->assertObjectHasAttribute('fieldTypeOptions', $response->sulu_admin);
        $this->assertInternalType('array', $response->sulu_admin->navigation);
        $this->assertInternalType('array', $response->sulu_admin->routes);
        $this->assertInternalType('object', $response->sulu_admin->resourceMetadataEndpoints);
        $this->assertObjectHasAttribute('sulu_preview', $response);
    }

    public function testGetNotExistingMetdata()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/metadata/test1/test');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testGetResourcePages()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/resources/pages');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $resource = json_decode($client->getResponse()->getContent());

        // check for datagrid
        $this->assertObjectHasAttribute('datagrid', $resource);
        $this->assertObjectHasAttribute('id', $resource->datagrid);

        $this->assertObjectHasAttribute('name', $resource->datagrid->id);
        $this->assertObjectHasAttribute('label', $resource->datagrid->id);
        $this->assertObjectHasAttribute('type', $resource->datagrid->id);

        $this->assertEquals('id', $resource->datagrid->id->name);
        $this->assertEquals('ID', $resource->datagrid->id->label);
        $this->assertEquals('string', $resource->datagrid->id->type);
    }

    public function testGetResourceContacts()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/resources/contacts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $resource = json_decode($client->getResponse()->getContent());

        // check for datagrid
        $this->assertObjectHasAttribute('datagrid', $resource);
        $this->assertObjectHasAttribute('id', $resource->datagrid);
        $this->assertObjectHasAttribute('title', $resource->datagrid);
        $this->assertObjectHasAttribute('account', $resource->datagrid);
        $this->assertObjectHasAttribute('firstName', $resource->datagrid);
    }

    public function testGetResourceAccounts()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/resources/accounts');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $resource = json_decode($client->getResponse()->getContent());

        // check for datagrid
        $this->assertObjectHasAttribute('datagrid', $resource);
        $this->assertObjectHasAttribute('id', $resource->datagrid);
        $this->assertObjectHasAttribute('name', $resource->datagrid);
        $this->assertObjectHasAttribute('zip', $resource->datagrid);
        $this->assertObjectHasAttribute('city', $resource->datagrid);
    }
}
