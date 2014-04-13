<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use DateTime;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class CollectionControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var Collection
     */
    protected static $collection;

    public function setUp()
    {
        $this->setUpSchema();
        $this->setUpCollection(self::$collection);
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\TestBundle\Entity\TestUser'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Collection'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\CollectionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\Media'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\MediaMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\File'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersion'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    protected function setUpCollection(&$collection)
    {
        // Collection
        $collection = new Collection();

        $style = array(
            'type' => 'circle',
            'color' => '#ffcc00'
        );

        $collection->setStyle(json_encode($style));

        $collection->setCreated(new DateTime());
        $collection->setChanged(new DateTime());

        // Create Collection Type
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');

        $collection->setType($collectionType);

        // Collection Meta 1
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        // Collection Meta 2
        $collectionMeta2 = new CollectionMeta();
        $collectionMeta2->setTitle('Test Kollektion');
        $collectionMeta2->setDescription('Dies ist eine Test Beschreibung');
        $collectionMeta2->setLocale('de');
        $collectionMeta2->setCollection($collection);

        $collection->addMeta($collectionMeta2);


        self::$em->persist($collection);
        self::$em->persist($collectionType);
        self::$em->persist($collectionMeta);
        self::$em->persist($collectionMeta2);

        self::$em->flush();
    }

    private function createTestClient() {
        return $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    /*
     * Tests
     */

    public function testTest() {
        $this->assertTrue(true);
    }

    /**
     * @description Test Collection GET by ID
     */
    public function testGetById() {

        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/collections/1'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $style = array(
            'type' => 'circle',
            'color' => '#ffcc00'
        );

        $this->assertEquals(json_encode($style), $response->style);
        $this->assertEquals(1, $response->id);
        $this->assertEquals(1, $response->type->id);
        $this->assertEquals(2, count($response->metas));
        $this->assertNotEmpty($response->created);
        $this->assertNotEmpty($response->changed);
        $this->assertEquals('Test Collection', $response->metas[0]->title);
        $this->assertEquals('This Description is only for testing', $response->metas[0]->description);
        $this->assertEquals('en-gb', $response->metas[0]->locale);
        $this->assertEquals('Test Kollektion', $response->metas[1]->title);
        $this->assertEquals('Dies ist eine Test Beschreibung', $response->metas[1]->description);
        $this->assertEquals('de', $response->metas[1]->locale);
    }

    /**
     * @description Test GET all Collections
     */
    public function testcGet() {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/collections'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
    }

    /**
     * @description Test GET for non existing Resource (404)
     */
    public function testGetByIdNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/collections/10'
        );


        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }


    /**
     * @description Test POST to create a new Collection
     */
    public function testPost()
    {
        $client = $this->createTestClient();

        $generateColor = Collection::generateColor();

        $this->assertNotEmpty($generateColor);
        $this->assertEquals(7, strlen($generateColor));

        $client->request(
            'POST',
            '/api/collections',
            array(
                'style' => json_encode(
                   array(
                       'type' => 'circle',
                       'color' => $generateColor
                   )
                ),
                'type' => array(
                    'id' => 1
                ),
                'metas' => array(
                    array(
                        'title' => 'Test Collection 2',
                        'description' => 'This Description 2 is only for testing',
                        'locale' => 'en-gb'
                    ),
                    array(
                        'title' => 'Test Kollektion 2',
                        'description' => 'Diese Beschreibung 2 ist zum Test',
                        'locale' => 'de'
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(json_encode(array(
            'type' => 'circle',
            'color' => $generateColor
        )), $response->style);
        $this->assertEquals(2, $response->id);
        $this->assertEquals(1, $response->type->id);
        $this->assertEquals(2, count($response->metas));
        $this->assertNotEmpty($response->created);
        $this->assertNotEmpty($response->changed);
        $this->assertEquals('Test Collection 2', $response->metas[0]->title);
        $this->assertEquals('This Description 2 is only for testing', $response->metas[0]->description);
        $this->assertEquals('en-gb', $response->metas[0]->locale);
        $this->assertEquals('Test Kollektion 2', $response->metas[1]->title);
        $this->assertEquals('Diese Beschreibung 2 ist zum Test', $response->metas[1]->description);
        $this->assertEquals('de', $response->metas[1]->locale);

        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/collections'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);
    }


    /**
     * @description Test POST to create a new Collection
     */
    public function testPostWithNotExistingType()
    {
        $client = $this->createTestClient();

        $generateColor = Collection::generateColor();

        $this->assertNotEmpty($generateColor);
        $this->assertEquals(7, strlen($generateColor));

        $client->request(
            'POST',
            '/api/collections',
            array(
                'style' => json_encode(
                    array(
                        'type' => 'circle',
                        'color' => $generateColor
                    )
                ),
                'type' => array(
                    'id' => 2
                ),
                'metas' => array(
                    array(
                        'title' => 'Test Collection 2',
                        'description' => 'This Description 2 is only for testing',
                        'locale' => 'en-gb'
                    ),
                    array(
                        'title' => 'Test Kollektion 2',
                        'description' => 'Diese Beschreibung 2 ist zum Test',
                        'locale' => 'de'
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertTrue(isset($response->message));
    }


    /**
     * @description Test Put Action
     */
    public function testPut()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/collections/1',
            array(
                'style' => json_encode(
                    array(
                        'type' => 'circle',
                        'color' => '#00ccff'
                    )
                ),
                'type' => array(
                    'id' => 1
                ),
                'metas' => array(
                    array(
                        'id' => 1,
                        'title' => 'Test Collection changed',
                        'description' => 'This Description is only for testing changed',
                        'locale' => 'en-au'
                    ),
                    array(
                        'id' => 2,
                        'title' => 'Test Kollektion geändert',
                        'description' => 'Diese Beschreibung ist zum Test geändert',
                        'locale' => 'de-de'
                    )
                )
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/collections/1'
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(json_encode(array(
            'type' => 'circle',
            'color' => '#00ccff'
        )), $response->style);
        $this->assertEquals(1, $response->id);
        $this->assertEquals(1, $response->type->id);
        $this->assertEquals(2, count($response->metas));
        $this->assertNotEmpty($response->created);
        $this->assertNotEmpty($response->changed);
        $this->assertEquals('Test Collection changed', $response->metas[0]->title);
        $this->assertEquals('This Description is only for testing changed', $response->metas[0]->description);
        $this->assertEquals('en-au', $response->metas[0]->locale);
        $this->assertEquals('Test Kollektion geändert', $response->metas[1]->title);
        $this->assertEquals('Diese Beschreibung ist zum Test geändert', $response->metas[1]->description);
        $this->assertEquals('de-de', $response->metas[1]->locale);

        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/collections'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);
    }


    public function testPutNoDetails()
    {
        $client = $this->createTestClient();

        // Add New Collection Type
        $collectionType = new CollectionType();
        $collectionType->setName('Second Collection Type');
        $collectionType->setDescription('Second Collection Type');

        self::$em->persist($collectionType);
        self::$em->flush();

        // Test put with only details
        $client->request(
            'PUT',
            '/api/collections/1',
            array(
                'style' => json_encode(
                    array(
                        'type' => 'quader',
                        'color' => '#00ccff'
                    )
                ),
                'type' => array(
                    'id' => 2
                )
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/collections/1'
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(json_encode(
            array(
                'type' => 'quader',
                'color' => '#00ccff'
            )
        ), $response->style);

        $this->assertEquals(2, $response->type->id);
        $this->assertEquals(2, count($response->metas));
    }

    /**
     * @description Test PUT on a none existing Object
     */
    public function testPutNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'PUT',
            '/api/collections/404',
            array(
                'style' => json_encode(
                    array(
                        'type' => 'quader',
                        'color' => '#00ccff'
                    )
                ),
                'type' => array(
                    'id' => 1
                )
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * @description Test DELETE
     */
    public function testDeleteById()
    {
        $client = $this->createTestClient();

        $client->request('DELETE', '/api/collections/1');
        $this->assertEquals('204', $client->getResponse()->getStatusCode());
    }

    /**
     * @description Test DELETE on none existing Object
     */
    public function testDeleteByIdNotExisting()
    {
        $client = $this->createTestClient();

        $client->request('DELETE', '/api/collections/404');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/collections?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    /**
     * @description Test GET all Collections
     */
    public function testcGet2() {

    }

}
