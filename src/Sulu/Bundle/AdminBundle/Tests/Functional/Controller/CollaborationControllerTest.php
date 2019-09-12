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
    public function setUp(): void
    {
        $collaborations = $this->getContainer()->get('cache.global_clearer')->clear('');
    }

    public function testPostWithSingleUser()
    {
        $client = $this->createAuthenticatedClient();
        $session = $client->getContainer()->get('session');
        $session->start();
        $client->request('PUT', '/admin/api/collaborations?id=4&resourceKey=page');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEmpty($response->_embedded->collaborations);

        $collaborations = array_values(
            $this->getContainer()->get('sulu_admin.collaboration_cache')->getItem('page_4')->get()
        );

        $this->assertCount(1, $collaborations);
        $this->assertEquals('page', $collaborations[0]->getResourceKey());
        $this->assertEquals('4', $collaborations[0]->getId());
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

        $client->request('PUT', '/admin/api/collaborations?id=4&resourceKey=page');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertCount(2, $response->_embedded->collaborations);

        $collaborations = $response->_embedded->collaborations;

        $this->assertEquals('page', $collaborations[0]->resourceKey);
        $this->assertEquals(4, $collaborations[0]->id);
        $this->assertEquals('Max Mustermann', $collaborations[0]->fullName);
        $this->assertObjectHasAttribute('connectionId', $collaborations[0]);
        $this->assertObjectHasAttribute('started', $collaborations[0]);
        $this->assertObjectHasAttribute('changed', $collaborations[0]);
        $this->assertEquals('page', $collaborations[1]->resourceKey);
        $this->assertEquals(4, $collaborations[1]->id);
        $this->assertEquals('Erika Mustermann', $collaborations[1]->fullName);
        $this->assertObjectHasAttribute('connectionId', $collaborations[1]);
        $this->assertObjectHasAttribute('started', $collaborations[1]);
        $this->assertObjectHasAttribute('changed', $collaborations[1]);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();
        $session = $client->getContainer()->get('session');
        $session->start();
        $cache = $this->getContainer()->get('sulu_admin.collaboration_cache');

        $cacheItem = $cache->getItem('page_4')->set([
            new Collaboration('collaboration1', 1, 'Max', 'Max Mustermann', 'page', 4),
            new Collaboration('collaboration2', 2, 'Erika', 'Erika Mustermann', 'page', 4),
        ]);

        $cache->save($cacheItem);

        $client->request('PUT', '/admin/api/collaborations?id=4&resourceKey=page');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(3, $cache->getItem('page_4')->get());

        $client->request('DELETE', '/admin/api/collaborations?id=4&resourceKey=page');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertCount(2, $cache->getItem('page_4')->get());
    }
}
