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

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TagControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TagRepositoryInterface
     */
    protected $tagRepository;

    protected function setUp()
    {
        $this->em = $this->getEntityManager();

        $this->session = $this->getContainer()->get('sulu_test.doctrine_phpcr')->getConnection();
        $this->tagRepository = $this->getContainer()->get('sulu.repository.tag');

        $this->initOrm();
    }

    protected function initOrm()
    {
        $this->purgeDatabase();
    }

    public function testGetById()
    {
        $tag = $this->createTag('tag1');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/tags/' . $tag->getId()
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('tag1', $response['name']);
        $this->assertNotContains('creator', array_keys($response));
        $this->assertNotContains('changer', array_keys($response));
    }

    public function testList()
    {
        $this->createTag('tag1');
        $this->createTag('tag2');
        $this->em->flush();

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

    public function testListFilteredByExcludedIds()
    {
        $tag1 = $this->createTag('tag1');
        $tag2 = $this->createTag('tag2');
        $this->createTag('tag3');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/tags?flat=true&excludedIds=' . $tag1->getId() . ',' . $tag2->getId()
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals('tag3', $response->_embedded->tags[0]->name);
    }

    public function testListFilteredByNames()
    {
        $this->createTag('tag1');
        $this->createTag('tag2');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/tags?flat=true&names=tag1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals('tag1', $response->_embedded->tags[0]->name);
    }

    public function testListSearch()
    {
        $this->createTag('tag1');
        $this->createTag('tag2');
        $this->em->flush();

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
        $this->createTag('tag1');
        $this->em->flush();

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
        $tag = $this->createTag('tag1');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/tags/' . $tag->getId(),
            ['name' => 'tag1_new']
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag1_new', $response->name);

        $client->request(
            'GET',
            '/api/tags/' . $tag->getId()
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('tag1_new', $response['name']);
        $this->assertNotContains('creator', array_keys($response));
        $this->assertNotContains('changer', array_keys($response));
    }

    public function testPutExistingName()
    {
        $tag1 = $this->createTag('tag1');
        $tag2 = $this->createTag('tag2');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/tags/' . $tag2->getId(),
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
        $tag1 = $this->createTag('tag1');
        $this->em->flush();

        $mockedEventListener = $this->getMockBuilder('Mock')->setMethods(['onDelete'])->getMock();
        $mockedEventListener->expects($this->once())->method('onDelete');

        $client = $this->createAuthenticatedClient();
        $client->getContainer()->get('event_dispatcher')->addListener(
            'sulu.tag.delete',
            [$mockedEventListener, 'onDelete']
        );

        $client->request(
            'DELETE',
            '/api/tags/' . $tag1->getId()
        );
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request(
            'GET',
            '/api/tags/' . $tag1->getId()
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
        $tag1 = $this->createTag('tag1');
        $tag2 = $this->createTag('tag2');
        $tag3 = $this->createTag('tag3');
        $tag4 = $this->createTag('tag4');
        $this->em->flush();

        $mockedEventListener = $this->getMockBuilder('Mock')->setMethods(['onMerge'])->getMock();
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
                $tag2->getId(), $tag3->getId(), $tag4->getId(),
            ]), 'dest' => $tag1->getId()]
        );
        $this->assertHttpStatusCode(303, $client->getResponse());
        $this->assertEquals('/api/tags/' . $tag1->getId(), $client->getResponse()->headers->get('location'));

        $client->request(
            'GET',
            '/api/tags/' . $tag1->getId()
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/tags/' . $tag2->getId()
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
        $tag1 = $this->createTag('tag1');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/tags/merge',
            ['src' => 1233, 'dest' => $tag1->getId()]
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Entity with the type "SuluTagBundle:Tag" and the id "1233" not found.', $response->message);
    }

    public function testPatch()
    {
        $this->createTag('tag1');
        $this->createTag('tag2');
        $this->em->flush();

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
        $this->createTag('tag1');
        $this->createTag('tag2');
        $this->em->flush();

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
        $tag1 = $this->createTag('tag1');
        $this->createTag('tag2');
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'PATCH',
            '/api/tags',
            [
                [
                    'id' => $tag1->getId(),
                    'name' => 'tag11',
                ],
                [
                    'name' => 'tag33',
                ],
            ]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('tag11', $response[0]->name);
        $this->assertEquals('tag33', $response[1]->name);

        $client->request(
            'GET',
            '/api/tags?flat=true'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(3, $response->total);
        $this->assertEquals('tag11', $response->_embedded->tags[0]->name);
        $this->assertEquals('tag2', $response->_embedded->tags[1]->name);
        $this->assertEquals('tag33', $response->_embedded->tags[2]->name);
    }

    public function createTag($name)
    {
        $tag = $this->tagRepository->createNew();
        $tag->setName($name);
        $this->em->persist($tag);

        return $tag;
    }
}
