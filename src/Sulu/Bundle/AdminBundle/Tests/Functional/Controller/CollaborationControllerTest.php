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

use Sulu\Bundle\AdminBundle\Entity\Collaboration;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CollaborationControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $collaborations = $this->getContainer()->get('cache.global_clearer')->clear('');
    }

    public function testPostWithSingleUser()
    {
        $client = $this->createAuthenticatedClient();
        $session = $client->getContainer()->get('session');
        $session->start();
        $client->request('POST', '/admin/api/collaborations', ['resourceKey' => 'page', 'id' => 4]);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEmpty($response->_embedded->collaborations);

        $collaborations = array_values(
            $this->getContainer()->get('sulu_admin.collaboration_cache')->getItem('page_4')->get()
        );

        $this->assertCount(1, $collaborations);
        $this->assertEquals('page', $collaborations[0]->getResourceKey());
        $this->assertEquals('page', $collaborations[0]->getResourceKey());
    }

    public function testPostWithMultipleUsers()
    {
        $client = $this->createAuthenticatedClient();
        $cache = $this->getContainer()->get('sulu_admin.collaboration_cache');

        $cacheItem = $cache->getItem('page_4')->set([
            new Collaboration('collaboration1', 1, 'Max', 'Max Mustermann', 'page', 4),
            new Collaboration('collaboration2', 2, 'Erika', 'Erika Mustermann', 'page', 4),
        ]);

        $cache->save($cacheItem);

        $client->request('POST', '/admin/api/collaborations', ['resourceKey' => 'page', 'id' => 4]);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertCount(2, $response->_embedded->collaborations);

        $collaborations = $response->_embedded->collaborations;

        $this->assertEquals('page', $collaborations[0]->resourceKey);
        $this->assertEquals(4, $collaborations[0]->id);
        $this->assertEquals('Max Mustermann', $collaborations[0]->fullName);
        $this->assertEquals('page', $collaborations[1]->resourceKey);
        $this->assertEquals(4, $collaborations[1]->id);
        $this->assertEquals('Erika Mustermann', $collaborations[1]->fullName);
    }
}
