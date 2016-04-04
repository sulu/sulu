<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Functional\Controller;

use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TagControllerTest extends SuluTestCase
{
    protected $em;

    protected function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();

        $this->initOrm();
    }

    protected function initOrm()
    {
        $this->purgeDatabase();

        $tag1 = new Tag();
        $tag1->setName('tag1');
        $this->em->persist($tag1);
        $this->tag1 = $tag1;

        $tag2 = new Tag();
        $tag2->setName('tag2');
        $this->em->persist($tag2);
        $this->tag2 = $tag2;

        $this->em->flush();
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/tags/' . $this->tag1->getId()
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('tag1', $response['name']);
        $this->assertNotContains('creator', array_keys($response));
        $this->assertNotContains('changer', array_keys($response));
    }

    public function testList()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/tags?flat=true'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals('tag1', $response->_embedded->tags[0]->name);
        $this->assertEquals('tag2', $response->_embedded->tags[1]->name);
    }

    public function testListSearch()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/tags?flat=true&search=tag2&searchFields=name'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals('tag2', $response->_embedded->tags[0]->name);
    }

    public function testGetByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/tags/11230'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/tags',
            ['name' => 'tag3']
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag3', $response->name);

        $client->request(
            'GET',
            '/api/tags/' . $response->id
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('tag3', $response['name']);
        $this->assertNotContains('creator', array_keys($response));
        $this->assertNotContains('changer', array_keys($response));
    }

    public function testPostExistingName()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/tags',
            ['name' => 'tag1']
        );

        $this->assertHttpStatusCode(400, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('A tag with the name "tag1"already exists!', $response->message);
        $this->assertEquals('name', $response->field);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/tags/' . $this->tag1->getId(),
            ['name' => 'tag1_new']
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag1_new', $response->name);

        $client->request(
            'GET',
            '/api/tags/' . $this->tag1->getId()
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('tag1_new', $response['name']);
        $this->assertNotContains('creator', array_keys($response));
        $this->assertNotContains('changer', array_keys($response));
    }

    public function testPutExistingName()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/tags/' . $this->tag2->getId(),
            ['name' => 'tag1']
        );

        $this->assertHttpStatusCode(400, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('A tag with the name "tag1"already exists!', $response->message);
        $this->assertEquals('name', $response->field);
    }

    public function testPutNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/tags/4711',
            ['name' => 'tag1_new']
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteById()
    {
        $mockedEventListener = $this->getMock('stdClass', ['onDelete']);
        $mockedEventListener->expects($this->once())->method('onDelete');

        $client = $this->createAuthenticatedClient();
        $client->getContainer()->get('event_dispatcher')->addListener(
            'sulu.tag.delete',
            [$mockedEventListener, 'onDelete']
        );

        $client->request(
            'DELETE',
            '/api/tags/' . $this->tag1->getId()
        );
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request(
            'GET',
            '/api/tags/' . $this->tag1->getId()
        );
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteByNotExistingId()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/api/tags/4711'
        );
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testMerge()
    {
        $tag3 = new Tag();
        $tag3->setName('tag3');
        $this->em->persist($tag3);

        $tag4 = new Tag();
        $tag4->setName('tag4');
        $this->em->persist($tag4);

        $this->em->flush();

        $mockedEventListener = $this->getMock('stdClass', ['onMerge']);
        $mockedEventListener->expects($this->once())->method('onMerge');

        $client = $this->createAuthenticatedClient();
        $client->getContainer()->get('event_dispatcher')->addListener(
            'sulu.tag.merge',
            [$mockedEventListener, 'onMerge']
        );

        $client->request(
            'POST',
            '/api/tags/merge',
            ['src' => implode(',', [
                $this->tag2->getId(), $tag3->getId(), $tag4->getId(),
            ]), 'dest' => $this->tag1->getId()]
        );
        $this->assertHttpStatusCode(303, $client->getResponse());
        $this->assertEquals('/admin/api/tags/' . $this->tag1->getId(), $client->getResponse()->headers->get('location'));

        $client->request(
            'GET',
            '/api/tags/' . $this->tag1->getId()
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/tags/' . $this->tag2->getId()
        );
        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request(
            'GET',
            '/api/tags/' . $tag3->getId()
        );
        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request(
            'GET',
            '/api/tags/' . $tag4->getId()
        );
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testMergeNotExisting()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/tags/merge',
            ['src' => 1233, 'dest' => $this->tag1->getId()]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Entity with the type "SuluTagBundle:Tag" and the id "1233" not found.', $response->message);
    }

    public function testPatch()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/tags',
            [
                [
                    'name' => 'tag3',
                ],
                [
                    'name' => 'tag4',
                ],
                [
                    'name' => 'tag5',
                ],
                [
                    'name' => 'tag6',
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag3', $response[0]->name);
        $this->assertEquals('tag4', $response[1]->name);
        $this->assertEquals('tag5', $response[2]->name);
        $this->assertEquals('tag6', $response[3]->name);

        $client->request(
            'GET',
            '/api/tags?flat=true'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(6, $response->total);
        $this->assertEquals('tag1', $response->_embedded->tags[0]->name);
        $this->assertEquals('tag2', $response->_embedded->tags[1]->name);
        $this->assertEquals('tag3', $response->_embedded->tags[2]->name);
        $this->assertEquals('tag4', $response->_embedded->tags[3]->name);
        $this->assertEquals('tag5', $response->_embedded->tags[4]->name);
        $this->assertEquals('tag6', $response->_embedded->tags[5]->name);
    }

    public function testPatchExistingAsNew()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/tags',
            [
                [
                    'name' => 'tag1',
                ],
                [
                    'name' => 'tag2',
                ],
            ]
        );

        $this->assertHttpStatusCode(400, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('A tag with the name "tag1"already exists!', $response->message);
        $this->assertEquals('name', $response->field);
    }

    public function testPatchExistingChange()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/tags',
            [
                [
                    'id' => $this->tag1->getId(),
                    'name' => 'tag11',
                ],
                [
                    'id' => $this->tag1->getId(),
                    'name' => 'tag22',
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('tag11', $response[0]->name);
        $this->assertEquals('tag22', $response[1]->name);

        $client->request(
            'GET',
            '/api/tags?flat=true'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals('tag1', $response->_embedded->tags[0]->name);
        $this->assertEquals('tag2', $response->_embedded->tags[1]->name);
        $this->assertEquals('tag11', $response->_embedded->tags[2]->name);
        $this->assertEquals('tag22', $response->_embedded->tags[3]->name);
    }
}
