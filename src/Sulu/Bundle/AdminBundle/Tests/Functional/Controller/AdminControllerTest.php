<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $collectionType = new LoadCollectionTypes();
        $collectionType->load($this->getEntityManager());
    }

    public function testGetConfig()
    {
        $client = $this->client;
        $client->request('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertObjectHasAttribute('sulu_admin', $response);
        $this->assertObjectHasAttribute('navigation', $response->sulu_admin);
        $this->assertObjectHasAttribute('resources', $response->sulu_admin);
        $this->assertObjectHasAttribute('routes', $response->sulu_admin);
        $this->assertObjectHasAttribute('fieldTypeOptions', $response->sulu_admin);
        $this->assertIsArray($response->sulu_admin->navigation);
        $this->assertIsArray($response->sulu_admin->routes);
        $this->assertIsObject($response->sulu_admin->resources);
        $this->assertObjectHasAttribute('sulu_preview', $response);
    }

    public function testGetNotExistingMetdata()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/metadata/test1/test');

        $this->assertHttpStatusCode(404, $client->getResponse());
    }
}
