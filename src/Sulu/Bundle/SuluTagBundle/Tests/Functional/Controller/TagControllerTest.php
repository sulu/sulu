<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Testing\DatabaseTestCase;

class TagControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    public function setUp()
    {
        $this->setUpSchema();

        $tag = new Tag();
        $tag->setName('tag1');
        $tag->setCreated(new \DateTime());
        $tag->setChanged(new \DateTime());
        self::$em->persist($tag);
        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function testGetById()
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/api/tags/1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('tag1', $response->name);
    }

    public function testGetByIdNotExisting()
    {
        $client = self::createClient();
        $client->request(
            'GET',
            '/api/tags/10'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    public function testPost()
    {
        $client = self::createClient();
        $client->request('POST', '/api/tags', array('name' => 'tag2'));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag2', $response->name);

        $client->request(
            'GET',
            '/api/tags/2'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('tag2', $response->name);
    }

    public function testPut()
    {
        $client = self::createClient();
        $client->request('PUT', '/api/tags/1', array('name' => 'tag1_new'));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag1_new', $response->name);

        $client->request(
            'GET',
            '/api/tags/1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('tag1_new', $response->name);
    }

    public function testPutNotExisting()
    {
        $client = self::createClient();
        $client->request('PUT', '/api/tags/4711', array('name' => 'tag1_new'));

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteById()
    {
        $eventListener = $this->getMock('stdClass', array('onDelete'));
        $eventListener->expects($this->once())->method('onDelete')->will($this->returnValue(1));

        // TODO test if event is thrown

        $client = static::createClient();

        $client->request('DELETE', '/api/tags/1');
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/tags/1');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());
    }

    public function testDeleteByNotExisitingId()
    {
        $client = static::createClient();

        $client->request('DELETE', '/api/tags/4711');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());
    }
}
