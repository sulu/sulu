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
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
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
        // Collection
        $collection = new Collection();

        $style = array(
            'type'  => 'circle',
            'color' => '#ffcc00'
        );

        $collection->setStyle(json_encode($style));

        $collection->setCreated(new DateTime());
        $collection->setChanged(new DateTime());
        $this->collection1 = $collection;

        // Create Collection Type
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');
        $this->collectionType1 = $collectionType;

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

        $this->em->persist($collection);
        $this->em->persist($collectionType);
        $this->em->persist($collectionMeta);
        $this->em->persist($collectionMeta2);

        $this->em->flush();
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
                'locale' => 'en-gb'
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $style = json_decode(json_encode(array(
            'type'  => 'circle',
            'color' => '#ffcc00'
        )), false);

        $this->assertEquals($style, $response->style);
        $this->assertEquals('This Description is only for testing', $response->description);
        $this->assertNotNull($response->id);
        $this->assertEquals(0, $response->mediaNumber);
        $this->assertCount(0, $response->thumbnails);
        $this->assertCount(0, $response->children);
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
                'locale' => 'en-gb'
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
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
                'locale'      => 'en-gb',
                'style' =>
                    array(
                        'type'  => 'circle',
                        'color' => $generateColor
                    )
            ,
                'type'  => array(
                    'id' => $this->collectionType1->getId(),
                ),
                'title'       => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'parent'      => null,
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
            '/api/collections',
            array(
                'locale' => 'en-gb'
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
     * @description Test POST to create a new Collection
     */
    public function testPostWithoutDetails()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/collections',
            array(
                'title'       => 'Test Collection 2',
                'type'  => array(
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
                'locale' => 'en-gb'
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
                'locale' => 'en'
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
                'style' =>
                    array(
                        'type'  => 'circle',
                        'color' => $generateColor
                    )
                ,
                'type'  => array(
                    'id' => 91283
                ),
                'title'       => 'Test Collection 2',
                'description' => 'This Description 2 is only for testing',
                'locale'      => 'en-gb'
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
                'style' =>
                    array(
                        'type'  => 'circle',
                        'color' => '#00ccff'
                    )
                ,
                'type'  => $this->collectionType1->getId(),
                'title'       => 'Test Collection changed',
                'description' => 'This Description is only for testing changed',
                'locale'      => 'en-gb'
            )
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/collections/' . $this->collection1->getId(),
            array(
                'locale'      => 'en-gb'
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
                'style' =>
                    array(
                        'type'  => 'circle',
                        'color' => '#00ccff'
                    )
            ,
                'type'  => 1,
                'title'       => 'Test Collection changed',
                'description' => 'This Description is only for testing changed',
            )
        );

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
                'style' =>
                    array(
                        'type'  => 'quader',
                        'color' => '#00ccff'
                    )
                ,
                'type'  => array (
                    'id' => $collectionType->getId()
                )
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
                'style' =>
                    array(
                        'type'  => 'quader',
                        'color' => '#00ccff'
                    )
                ,
                'type'  => $this->collectionType1->getId(),
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
}
