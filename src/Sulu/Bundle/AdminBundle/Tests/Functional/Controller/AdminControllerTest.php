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
}
