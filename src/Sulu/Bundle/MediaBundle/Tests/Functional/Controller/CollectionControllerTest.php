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

use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class CollectionControllerTest extends SuluTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->purgeDatabase();
        $this->em = $this->db('ORM')->getOm();
        $this->initOrm();
    }

    protected function initOrm()
    {
        $this->collection1 = $this->createCollection(
            'Default Collection Type',
            array('en-gb' => 'Test Collection', 'de' => 'Test Kollektion')
        );
        $this->collectionType1 = $this->collection1->getType();
    }

    private function createCollection($typeName, $title = array(), $parent = null)
    {
        // Collection
        $collection = new Collection();

        $style = array(
            'type' => 'circle',
            'color' => '#ffcc00',
        );

        $collection->setStyle(json_encode($style));

        // Create Collection Type
        $collectionType = new CollectionType();
        $collectionType->setName($typeName);
        $collectionType->setDescription('Default Collection Type');

        $collection->setType($collectionType);

        // Collection Meta 1
        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle(isset($title['en-gb']) ? $title['en-gb'] : 'Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->setDefaultMeta($collectionMeta);
        $collection->addMeta($collectionMeta);

        // Collection Meta 2
        $collectionMeta2 = new CollectionMeta();
        $collectionMeta2->setTitle(isset($title['de']) ? $title['de'] : 'Kollection');
        $collectionMeta2->setDescription('Dies ist eine Test Beschreibung');
        $collectionMeta2->setLocale('de');
        $collectionMeta2->setCollection($collection);

        $collection->addMeta($collectionMeta2);

        $collection->setParent($parent);

        $this->em->persist($collection);
        $this->em->persist($collectionType);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionMeta2);

        $this->em->flush();

        return $collection;
    }

    /**
     * @description Test Collection GET by ID
     */
    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections/' . $this->collection1->getId(),
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $style = json_decode(
            json_encode(
                array(
                    'type' => 'circle',
                    'color' => '#ffcc00',
                )
            ),
            false
        );

        $this->assertEquals($style, $response->style);
        $this->assertEquals('This Description is only for testing', $response->description);
        $this->assertNotNull($response->id);
        $this->assertCount(0, $response->_embedded->collections);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('Test Collection', $response->title);
        $this->assertNotNull($response->type->id);
        $this->assertEquals('Default Collection Type', $response->type->name);
        $this->assertEquals('Default Collection Type', $response->type->description);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->changed)));
    }

    /**
     * @description Test GET all Collections
     */
    public function testcGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $this->assertNotEmpty($response->_embedded->collections);

        $this->assertCount(1, $response->_embedded->collections);
    }

    /**
     * @description Test GET for non existing Resource (404)
     */
    public function testGetByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections/10'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5005, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * @description Test POST to create a new Collection
     */
    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $generateColor = '#ffcc00';

        $this->assertNotEmpty($generateColor);
        $this->assertEquals(7, strlen($generateColor));

        $client->request(
            'POST',
            '/api/collections',
            array(
                'locale' => 'en-gb',
                'style' => array(
                        'type' => 'circle',
                        'color' => $generateColor,
                    ),
                'type' => array(
                    'id' => $this->collectionType1->getId(),
                ),
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'parent' => null,
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals($style, $response->style);
        $this->assertNotNull($response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->changed)));
        $this->assertEquals('Test Collection 2', $response->title);
        $this->assertEquals('This Description 2 is only for testing', $response->description);
        /*
        $this->assertNotEmpty($response->creator);
        $this->assertNotEmpty($response->changer);
        */

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections?flat=true',
            array(
                'locale' => 'en-gb',
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#ffcc00';

        $this->assertEquals($this->collection1->getId(), $responseFirstEntity->id);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertEquals($this->collectionType1->getId(), $responseFirstEntity->type->id);
        $this->assertNotEmpty($responseFirstEntity->created);
        $this->assertNotEmpty($responseFirstEntity->changed);
        $this->assertEquals('Test Collection', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing', $responseFirstEntity->description);

        // check second entity was created right
        $this->assertTrue(isset($response->_embedded->collections[1]));
        $responseSecondEntity = $response->_embedded->collections[1];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertNotNull($responseSecondEntity->id);
        $this->assertEquals('en-gb', $responseSecondEntity->locale);
        $this->assertEquals($style, $responseSecondEntity->style);
        $this->assertNotNull($responseSecondEntity->type->id);
        $this->assertNotEmpty($responseSecondEntity->created);
        $this->assertNotEmpty($responseSecondEntity->changed);
        $this->assertEquals('Test Collection 2', $responseSecondEntity->title);
        $this->assertEquals('This Description 2 is only for testing', $responseSecondEntity->description);
    }

    /**
     * @description Test POST to create a new nested Collection
     */
    public function testPostNested()
    {
        $client = $this->createAuthenticatedClient();

        $generateColor = '#ffcc00';

        $this->assertNotEmpty($generateColor);
        $this->assertEquals(7, strlen($generateColor));

        $client->request(
            'POST',
            '/api/collections',
            array(
                'locale' => 'en-gb',
                'style' => array(
                    'type' => 'circle',
                    'color' => $generateColor,
                ),
                'type' => array(
                    'id' => $this->collectionType1->getId(),
                ),
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'parent' => $this->collection1->getId(),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals($style, $response->style);
        $this->assertNotNull($response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->changed)));
        $this->assertEquals('Test Collection 2', $response->title);
        $this->assertEquals('This Description 2 is only for testing', $response->description);
        $this->assertEquals($this->collection1->getId(), $response->_embedded->parent->id);

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections/' . $this->collection1->getId() . '?depth=1',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = $generateColor;

        $this->assertNotNull($responseFirstEntity->id);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertNotEmpty($responseFirstEntity->created);
        $this->assertNotEmpty($responseFirstEntity->changed);
        $this->assertEquals('Test Collection 2', $responseFirstEntity->title);
        $this->assertEquals('This Description 2 is only for testing', $responseFirstEntity->description);

        $client->request(
            'GET',
            '/api/collections?flat=true&depth=2',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, $response->total);
    }

    /**
     * @description Test POST to create a new Collection
     */
    public function testPostWithoutDetails()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/collections',
            array(
                'title' => 'Test Collection 2',
                'type' => array(
                    'id' => $this->collectionType1->getId(),
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('en', $response->locale);
        $this->assertNotNull($response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->changed)));
        $this->assertEquals('Test Collection 2', $response->title);

        // get collection in locale 'en-gb'
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections',
            array(
                'locale' => 'en-gb',
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#ffcc00';

        $this->assertEquals($this->collection1->getId(), $responseFirstEntity->id);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->changed)));
        $this->assertEquals('Test Collection', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing', $responseFirstEntity->description);

        // check second entity was created right
        $this->assertTrue(isset($response->_embedded->collections[1]));
        $responseSecondEntity = $response->_embedded->collections[1];

        $this->assertNotNull($responseSecondEntity->id);
        $this->assertEquals('en-gb', $responseSecondEntity->locale);
        $this->assertNotNull($responseSecondEntity->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseSecondEntity->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseSecondEntity->changed)));
        $this->assertEquals('Test Collection 2', $responseSecondEntity->title);

        // get collection in locale 'en'
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections',
            array(
                'locale' => 'en',
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        // check if first entity is unchanged
        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#ffcc00';

        $this->assertNotNull($responseFirstEntity->id);
        $this->assertEquals('en', $responseFirstEntity->locale);
        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->changed)));
        $this->assertEquals('Test Collection', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing', $responseFirstEntity->description);

        // check second entity was created right
        $this->assertTrue(isset($response->_embedded->collections[1]));
        $responseSecondEntity = $response->_embedded->collections[1];

        $this->assertNotNull($responseSecondEntity->id);
        $this->assertEquals('en', $responseSecondEntity->locale);
        $this->assertNotNull($responseSecondEntity->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseSecondEntity->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseSecondEntity->changed)));
        $this->assertEquals('Test Collection 2', $responseSecondEntity->title);
    }

    /**
     * @description Test POST to create a new Collection
     */
    public function testPostWithNotExistingType()
    {
        $client = $this->createAuthenticatedClient();

        $generateColor = '#ffcc00';

        $this->assertNotEmpty($generateColor);
        $this->assertEquals(7, strlen($generateColor));

        $client->request(
            'POST',
            '/api/collections',
            array(
                'style' => array(
                        'type' => 'circle',
                        'color' => $generateColor,
                    ),
                'type' => array(
                    'id' => 91283,
                ),
                'title' => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertTrue(isset($response->message));
    }

    /**
     * @description Test PUT Action
     */
    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/collections/' . $this->collection1->getId(),
            array(
                'style' => array(
                    'type' => 'circle',
                    'color' => '#00ccff',
                ),
                'type' => $this->collectionType1->getId(),
                'title' => 'Test Collection changed',
                'description' => 'This Description is only for testing changed',
                'locale' => 'en-gb',
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/collections/' . $this->collection1->getId(),
            array(
                'locale' => 'en-gb',
            )
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#00ccff';

        $this->assertEquals($style, $response->style);
        $this->assertEquals($this->collection1->getId(), $response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->changed)));
        $this->assertEquals('Test Collection changed', $response->title);
        $this->assertEquals('This Description is only for testing changed', $response->description);
        $this->assertEquals('en-gb', $response->locale);

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections?locale=en-gb'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);

        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#00ccff';

        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertEquals($this->collection1->getId(), $responseFirstEntity->id);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->changed)));
        $this->assertEquals('Test Collection changed', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing changed', $responseFirstEntity->description);
        $this->assertEquals('en-gb', $responseFirstEntity->locale);
    }

    /**
     * @description Test PUT Action
     */
    public function testPutWithoutLocale()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/collections/' . $this->collection1->getId(),
            array(
                'style' => array(
                    'type' => 'circle',
                    'color' => '#00ccff',
                ),
                'type' => 1,
                'title' => 'Test Collection changed',
                'description' => 'This Description is only for testing changed',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/collections/' . $this->collection1->getId()
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#00ccff';

        $this->assertEquals($style, $response->style);
        $this->assertEquals($this->collection1->getId(), $response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($response->changed)));
        $this->assertEquals('Test Collection changed', $response->title);
        $this->assertEquals('This Description is only for testing changed', $response->description);
        $this->assertEquals('en', $response->locale);

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections?locale=en'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);

        $this->assertTrue(isset($response->_embedded->collections[0]));
        $responseFirstEntity = $response->_embedded->collections[0];

        $style = new \stdClass();
        $style->type = 'circle';
        $style->color = '#00ccff';

        $this->assertEquals($style, $responseFirstEntity->style);
        $this->assertEquals($this->collection1->getId(), $responseFirstEntity->id);
        $this->assertNotNull($responseFirstEntity->type->id);
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->created)));
        $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($responseFirstEntity->changed)));
        $this->assertEquals('Test Collection changed', $responseFirstEntity->title);
        $this->assertEquals('This Description is only for testing changed', $responseFirstEntity->description);
        $this->assertEquals('en', $responseFirstEntity->locale);
    }

    /**
     * @description Test PUT action without details
     */
    public function testPutNoDetails()
    {
        $client = $this->createAuthenticatedClient();

        // Add New Collection Type
        $collectionType = new CollectionType();
        $collectionType->setName('Second Collection Type');
        $collectionType->setDescription('Second Collection Type');

        $this->em->persist($collectionType);
        $this->em->flush();

        // Test put with only details
        $client->request(
            'PUT',
            '/api/collections/' . $this->collection1->getId(),
            array(
                'style' => array(
                        'type' => 'quader',
                        'color' => '#00ccff',
                    ),
                'type' => array(
                    'id' => $collectionType->getId(),
                ),
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/collections/' . $this->collection1->getId() . '?locale=en-gb'
        );
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $style = new \stdClass();
        $style->type = 'quader';
        $style->color = '#00ccff';

        $this->assertEquals($style, $response->style);

        $this->assertNotNull($response->type->id);
    }

    /**
     * @description Test PUT on a none existing Object
     */
    public function testPutNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/collections/404',
            array(
                'style' => array(
                        'type' => 'quader',
                        'color' => '#00ccff',
                    ),
                'type' => $this->collectionType1->getId(),
            )
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    /**
     * @description Test DELETE
     */
    public function testDeleteById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/collections/' . $this->collection1->getId());
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/collections/' . $this->collection1->getId()
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5005, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * @description Test DELETE on none existing Object
     */
    public function testDeleteByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/collections/404');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/collections?flat=true');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    private function prepareTree()
    {
        $collection1 = $this->collection1;
        $collection2 = $this->createCollection('My Type 1', array('en-gb' => 'col2'), $collection1);
        $collection3 = $this->createCollection('My Type 2', array('en-gb' => 'col3'), $collection1);
        $collection4 = $this->createCollection('My Type 3', array('en-gb' => 'col4'));
        $collection5 = $this->createCollection('My Type 4', array('en-gb' => 'col5'), $collection4);
        $collection6 = $this->createCollection('My Type 5', array('en-gb' => 'col6'), $collection4);
        $collection7 = $this->createCollection('My Type 6', array('en-gb' => 'col7'), $collection6);

        return array(
            array(
                'Test Collection',
                'col2',
                'col3',
                'col4',
                'col5',
                'col6',
                'col7',
            ),
            array(
                $collection1->getId(),
                $collection2->getId(),
                $collection3->getId(),
                $collection4->getId(),
                $collection5->getId(),
                $collection6->getId(),
                $collection7->getId(),
            ),
            array(
                $collection1,
                $collection2,
                $collection3,
                $collection4,
                $collection5,
                $collection6,
                $collection7,
            ),
        );
    }

    public function testCGetNestedFlat()
    {
        list($titles) = $this->prepareTree();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections?flat=true',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);
        $this->assertEquals(2, $response->total);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $response->_embedded->collections;

        $this->assertEquals($titles[0], $items[0]->title);
        $this->assertEquals(null, $items[0]->_embedded->parent);
        $this->assertEmpty($items[0]->_embedded->collections);
        $this->assertEquals($titles[3], $items[1]->title);
        $this->assertEquals(null, $items[1]->_embedded->parent);
        $this->assertEmpty($items[1]->_embedded->collections);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections?flat=true&depth=1',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);
        $this->assertEquals(6, $response->total);
        $this->assertCount(6, $response->_embedded->collections);
        $items = $response->_embedded->collections;

        $this->assertEquals($titles[0], $items[0]->title);
        $this->assertNull($items[0]->_embedded->parent);
        $this->assertEmpty($items[0]->_embedded->collections);

        $this->assertEquals($titles[1], $items[1]->title);
        $this->assertNotNull($items[1]->_embedded->parent);
        $this->assertEmpty($items[1]->_embedded->collections);

        $this->assertEquals($titles[2], $items[2]->title);
        $this->assertNotNull($items[2]->_embedded->parent);
        $this->assertEmpty($items[2]->_embedded->collections);

        $this->assertEquals($titles[3], $items[3]->title);
        $this->assertNull($items[3]->_embedded->parent);
        $this->assertEmpty($items[3]->_embedded->collections);

        $this->assertEquals($titles[4], $items[4]->title);
        $this->assertNotNull($items[4]->_embedded->parent);
        $this->assertEmpty($items[4]->_embedded->collections);

        $this->assertEquals($titles[5], $items[5]->title);
        $this->assertNotNull($items[5]->_embedded->parent);
        $this->assertEmpty($items[5]->_embedded->collections);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections?flat=true&depth=2',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);
        $this->assertEquals(7, $response->total);
        $this->assertCount(7, $response->_embedded->collections);
        $items = $response->_embedded->collections;

        $this->assertEquals($titles[0], $items[0]->title);
        $this->assertNull($items[0]->_embedded->parent);
        $this->assertEmpty($items[0]->_embedded->collections);

        $this->assertEquals($titles[1], $items[1]->title);
        $this->assertNotNull($items[1]->_embedded->parent);
        $this->assertEmpty($items[1]->_embedded->collections);

        $this->assertEquals($titles[2], $items[2]->title);
        $this->assertNotNull($items[2]->_embedded->parent);
        $this->assertEmpty($items[2]->_embedded->collections);

        $this->assertEquals($titles[3], $items[3]->title);
        $this->assertNull($items[3]->_embedded->parent);
        $this->assertEmpty($items[3]->_embedded->collections);

        $this->assertEquals($titles[4], $items[4]->title);
        $this->assertNotNull($items[4]->_embedded->parent);
        $this->assertEmpty($items[4]->_embedded->collections);

        $this->assertEquals($titles[5], $items[5]->title);
        $this->assertNotNull($items[5]->_embedded->parent);
        $this->assertEmpty($items[5]->_embedded->collections);

        $this->assertEquals($titles[6], $items[6]->title);
        $this->assertNotNull($items[6]->_embedded->parent);
        $this->assertEmpty($items[6]->_embedded->collections);
    }

    public function testCGetNestedTree()
    {
        list($titles) = $this->prepareTree();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $response->_embedded->collections;

        $this->assertEquals($titles[0], $items[0]->title);
        $this->assertEquals(null, $items[0]->_embedded->parent);
        $this->assertEmpty($items[0]->_embedded->collections);
        $this->assertEquals($titles[3], $items[1]->title);
        $this->assertEquals(null, $items[1]->_embedded->parent);
        $this->assertEmpty($items[1]->_embedded->collections);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections?depth=1',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $response->_embedded->collections;

        $this->assertEquals($titles[0], $items[0]->title);
        $this->assertNull($items[0]->_embedded->parent);
        $this->assertNotEmpty($items[0]->_embedded->collections);

        $this->assertEquals($titles[3], $items[1]->title);
        $this->assertNull($items[1]->_embedded->parent);
        $this->assertNotEmpty($items[1]->_embedded->collections);

        $subItems = $items[0]->_embedded->collections;
        $this->assertCount(2, $subItems);
        $this->assertEquals($titles[1], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[2], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertEmpty($subItems[1]->_embedded->collections);

        $subItems = $items[1]->_embedded->collections;
        $this->assertCount(2, $subItems);
        $this->assertEquals($titles[4], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[5], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertEmpty($subItems[1]->_embedded->collections);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections?depth=2',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertNotEmpty($response);
        $this->assertCount(2, $response->_embedded->collections);
        $items = $response->_embedded->collections;

        $this->assertEquals($titles[0], $items[0]->title);
        $this->assertNull($items[0]->_embedded->parent);
        $this->assertNotEmpty($items[0]->_embedded->collections);

        $this->assertEquals($titles[3], $items[1]->title);
        $this->assertNull($items[1]->_embedded->parent);
        $this->assertNotEmpty($items[1]->_embedded->collections);

        $subItems = $items[0]->_embedded->collections;
        $this->assertCount(2, $subItems);
        $this->assertEquals($titles[1], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[2], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertEmpty($subItems[1]->_embedded->collections);

        $subItems = $items[1]->_embedded->collections;
        $this->assertCount(2, $subItems);
        $this->assertEquals($titles[4], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[5], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertNotEmpty($subItems[1]->_embedded->collections);

        $subItems = $subItems[1]->_embedded->collections;
        $this->assertCount(1, $subItems);
        $this->assertEquals($titles[6], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);
    }

    public function testGetBreadcrumb()
    {
        list($titles, $ids) = $this->prepareTree();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[6],
            array(
                'locale' => 'en-gb',
                'breadcrumb' => 'true',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($titles[6], $response['title']);

        $breadcrumb = $response['_embedded']['breadcrumb'];
        $this->assertCount(2, $breadcrumb);
        $this->assertEquals($titles[3], $breadcrumb[0]['title']);
        $this->assertEquals($ids[3], $breadcrumb[0]['id']);
        $this->assertEquals($titles[5], $breadcrumb[1]['title']);
        $this->assertEquals($ids[5], $breadcrumb[1]['id']);
    }

    /**
     * @description Test Collection GET by ID with a depth
     */
    public function testGetByIdWithDepth()
    {
        list($titles, $ids) = $this->prepareTree();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[3],
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($titles[3], $response->title);
        $this->assertNull($response->_embedded->parent);
        $this->assertEmpty($response->_embedded->collections);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=1',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($titles[3], $response->title);
        $this->assertNull($response->_embedded->parent);
        $this->assertNotEmpty($response->_embedded->collections);

        $subItems = $response->_embedded->collections;
        $this->assertCount(2, $subItems);
        $this->assertEquals($titles[4], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[5], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertEmpty($subItems[1]->_embedded->collections);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=2',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($titles[3], $response->title);
        $this->assertNull($response->_embedded->parent);
        $this->assertNotEmpty($response->_embedded->collections);

        $subItems = $response->_embedded->collections;
        $this->assertCount(2, $subItems);
        $this->assertEquals($titles[4], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[5], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertNotEmpty($subItems[1]->_embedded->collections);

        $subItems = $subItems[1]->_embedded->collections;
        $this->assertCount(1, $subItems);
        $this->assertEquals($titles[6], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);
    }

    /**
     * @description Test move a Collection
     */
    public function testMove()
    {
        list($titles, $ids) = $this->prepareTree();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/collections/' . $ids[3] . '?action=move&destination=' . $ids[0],
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($ids[0], $response->_embedded->parent->id);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections?depth=3',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $items = $response->_embedded->collections;

        // all items in this response
        $this->assertEquals(7, $response->total);

        // root collection items
        $this->assertCount(1, $items);

        $this->assertEquals($titles[0], $items[0]->title);
        $this->assertNull($items[0]->_embedded->parent);
        $this->assertNotEmpty($items[0]->_embedded->collections);

        $subItems = $items[0]->_embedded->collections;
        $this->assertCount(3, $subItems);
        $this->assertEquals($titles[1], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[2], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertEmpty($subItems[1]->_embedded->collections);

        $this->assertEquals($titles[3], $subItems[2]->title);
        $this->assertNotNull($subItems[2]->_embedded->parent);
        $this->assertNotEmpty($subItems[2]->_embedded->collections);

        $subItems = $subItems[2]->_embedded->collections;
        $this->assertCount(2, $subItems);
        $this->assertEquals($titles[4], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);

        $this->assertEquals($titles[5], $subItems[1]->title);
        $this->assertNotNull($subItems[1]->_embedded->parent);
        $this->assertNotEmpty($subItems[1]->_embedded->collections);

        $subItems = $subItems[1]->_embedded->collections;
        $this->assertCount(1, $subItems);
        $this->assertEquals($titles[6], $subItems[0]->title);
        $this->assertNotNull($subItems[0]->_embedded->parent);
        $this->assertEmpty($subItems[0]->_embedded->collections);
    }

    public function testSearchChildren()
    {
        list($titles, $ids) = $this->prepareTree();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=1&search=col5',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($titles[3], $response['title']);
        $this->assertcount(1, $response['_embedded']['collections']);
        $this->assertEquals($titles[4], $response['_embedded']['collections'][0]['title']);
    }

    public function testPaginationChildren()
    {
        list($titles, $ids, $collections) = $this->prepareTree();
        $this->createCollection('My new type', array('en-gb' => 'my collection'), $collections[3]);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=1&page=1&limit=2',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($titles[3], $response['title']);
        $this->assertcount(2, $response['_embedded']['collections']);
        $this->assertEquals($titles[4], $response['_embedded']['collections'][0]['title']);
        $this->assertEquals($titles[5], $response['_embedded']['collections'][1]['title']);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=1&page=2&limit=2',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($titles[3], $response['title']);
        $this->assertcount(1, $response['_embedded']['collections']);
        $this->assertEquals('my collection', $response['_embedded']['collections'][0]['title']);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/collections/' . $ids[3] . '?depth=1&page=1&limit=10',
            array(
                'locale' => 'en-gb',
            )
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($titles[3], $response['title']);
        $this->assertcount(3, $response['_embedded']['collections']);
        $this->assertEquals($titles[4], $response['_embedded']['collections'][0]['title']);
        $this->assertEquals($titles[5], $response['_embedded']['collections'][1]['title']);
        $this->assertEquals('my collection', $response['_embedded']['collections'][2]['title']);
    }
}
