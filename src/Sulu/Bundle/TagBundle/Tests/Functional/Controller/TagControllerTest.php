<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Functional\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class TagControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    public function setUp()
    {
        $this->setUpSchema();

        $tag1 = new Tag();
        $tag1->setName('tag1');
        $tag1->setCreated(new \DateTime());
        $tag1->setChanged(new \DateTime());
        self::$em->persist($tag1);

        $tag2 = new Tag();
        $tag2->setName('tag2');
        $tag2->setCreated(new \DateTime());
        $tag2->setChanged(new \DateTime());
        self::$em->persist($tag2);

        self::$em->flush();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag'),
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser'),
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestContact'),
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
            '/api/tags/1',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('tag1', $response->name);
    }

    public function testList()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/tags?flat=true',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );


        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals('tag1', $response->_embedded->tags[0]->name);
        $this->assertEquals('tag2', $response->_embedded->tags[1]->name);
    }

    public function testGetByIdNotExisting()
    {
        $client = self::createClient();
        $client->request(
            'GET',
            '/api/tags/10',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    public function testPost()
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/tags',
            array('name' => 'tag3'),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag3', $response->name);

        $client->request(
            'GET',
            '/api/tags/3'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('tag3', $response->name);
    }

    public function testPostExistingName()
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/tags',
            array('name' => 'tag1'),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('A tag with the name "tag1"already exists!', $response->message);
        $this->assertEquals('name', $response->field);
    }

    public function testPut()
    {
        $client = self::createClient();
        $client->request(
            'PUT',
            '/api/tags/1',
            array('name' => 'tag1_new'),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag1_new', $response->name);

        $client->request(
            'GET',
            '/api/tags/1',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('tag1_new', $response->name);
    }

    public function testPutExistingName()
    {
        $client = self::createClient();
        $client->request(
            'PUT',
            '/api/tags/2',
            array('name' => 'tag1'),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('A tag with the name "tag1"already exists!', $response->message);
        $this->assertEquals('name', $response->field);
    }

    public function testPutNotExisting()
    {
        $client = self::createClient();
        $client->request(
            'PUT',
            '/api/tags/4711',
            array('name' => 'tag1_new'),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteById()
    {
        $mockedEventListener = $this->getMock('stdClass', array('onDelete'));
        $mockedEventListener->expects($this->once())->method('onDelete');

        $client = static::createClient();
        $client->getContainer()->get('event_dispatcher')->addListener(
            'sulu.tag.delete',
            array($mockedEventListener, 'onDelete')
        );

        $client->request(
            'DELETE',
            '/api/tags/1',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/tags/1',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals('404', $client->getResponse()->getStatusCode());
    }

    public function testDeleteByNotExistingId()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/tags/4711',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals('404', $client->getResponse()->getStatusCode());
    }

    public function testMerge()
    {
        $tag = new Tag();
        $tag->setName('tag3');
        $tag->setCreated(new \DateTime());
        $tag->setChanged(new \DateTime());
        self::$em->persist($tag);

        $tag = new Tag();
        $tag->setName('tag4');
        $tag->setCreated(new \DateTime());
        $tag->setChanged(new \DateTime());
        self::$em->persist($tag);

        self::$em->flush();

        $mockedEventListener = $this->getMock('stdClass', array('onMerge'));
        $mockedEventListener->expects($this->once())->method('onMerge');

        $client = static::createClient();
        $client->getContainer()->get('event_dispatcher')->addListener(
            'sulu.tag.merge',
            array($mockedEventListener, 'onMerge')
        );

        $client->request(
            'POST',
            '/api/tags/merge',
            array('src' => implode(',', array(2, 3, 4)), 'dest' => 1),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals(303, $client->getResponse()->getStatusCode());
        $this->assertEquals('/admin/api/tags/1', $client->getResponse()->headers->get('location'));

        $client->request(
            'GET',
            '/api/tags/1',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/tags/2',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/tags/3',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/tags/4',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testMergeNotExisting()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/tags/merge',
            array('src' => 3, 'dest' => 1),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Entity with the type "SuluTagBundle:Tag" and the id "3" not found.', $response->message);
    }

    public function testPatch()
    {
        $client = self::createClient();
        $client->request(
            'PATCH',
            '/api/tags',
            array(
                array(
                    'name' => 'tag3'
                ),
                array(
                    'name' => 'tag4'
                ),
                array(
                    'name' => 'tag5'
                ),
                array(
                    'name' => 'tag6'
                )
            ),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('tag3', $response[0]->name);
        $this->assertEquals('tag4', $response[1]->name);
        $this->assertEquals('tag5', $response[2]->name);
        $this->assertEquals('tag6', $response[3]->name);

        $client->request(
            'GET',
            '/api/tags?flat=true',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        echo $client->getResponse()->getContent();

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
        $client = self::createClient();
        $client->request(
            'PATCH',
            '/api/tags',
            array(
                array(
                    'name' => 'tag1'
                ),
                array(
                    'name' => 'tag2'
                )
            ),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('A tag with the name "tag1"already exists!', $response->message);
        $this->assertEquals('name', $response->field);

    }

    public function testPatchExistingChange()
    {
        $client = self::createClient();
        $client->request(
            'PATCH',
            '/api/tags',
            array(
                array(
                    'id' => 1,
                    'name' => 'tag11'
                ),
                array(
                    'id' => 2,
                    'name' => 'tag22'
                )
            ),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('tag11', $response[0]->name);
        $this->assertEquals('tag22', $response[1]->name);

        $client->request(
            'GET',
            '/api/tags?flat=true',
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test'
            )
        );


        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(4, $response->total);
        $this->assertEquals('tag1', $response->_embedded->tags[0]->name);
        $this->assertEquals('tag2', $response->_embedded->tags[1]->name);
        $this->assertEquals('tag11', $response->_embedded->tags[2]->name);
        $this->assertEquals('tag22', $response->_embedded->tags[3]->name);

    }
}
