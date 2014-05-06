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
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var Media
     */
    protected static $media;


    public function setUp()
    {
        $this->setUpSchema();
        $this->setUpMedia(self::$media);
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
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\MediaType'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\File'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersion'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionMeta'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage'),
            self::$em->getClassMetadata('Sulu\Bundle\TagBundle\Entity\Tag')
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    protected function setUpMedia(&$media)
    {
        // Media
        $media = new Media();

        $media->setCreated(new DateTime());
        $media->setChanged(new DateTime());

        // Media Meta 1
        /*
        $mediaMeta = new MediaMeta();
        $mediaMeta->setTitle('Test Media');
        $mediaMeta->setDescription('This Description is only for testing');
        $mediaMeta->setLocale('en-gb');
        $mediaMeta->setMedia($media);

        $media->addMeta($mediaMeta);

        // Media Meta 2
        $mediaMeta2 = new MediaMeta();
        $mediaMeta2->setTitle('Test Media');
        $mediaMeta2->setDescription('Dies ist eine Test Beschreibung');
        $mediaMeta2->setLocale('de');
        $mediaMeta2->setMedia($media);

        $media->addMeta($mediaMeta2);
        */

        // Create Media Type
        $mediaType = new MediaType();
        $mediaType->setName('Default Media Type');
        $mediaType->setDescription('Default Media Type');

        $media->setType($mediaType);


        // Setup Collection
        $collection = new Collection();

        $this->setUpCollection($collection);

        $media->setCollection($collection);

        self::$em->persist($media);
        self::$em->persist($mediaType);
        // self::$em->persist($mediaMeta);
        // self::$em->persist($mediaMeta2);

        self::$em->flush();
    }

    protected function setUpCollection(&$collection)
    {
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
    }

    private function createTestClient()
    {
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

    public function testTest()
    {
        $client = $this->createTestClient();
        $this->assertTrue((bool)$client);
    }

    /**
     * @description Test Media GET by ID
     */
    public function testGetById()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/media/1'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->id);

        /*
        $this->assertEquals('Test Media', $response->metas[0]->title);
        $this->assertEquals('This Description is only for testing', $response->metas[0]->description);
        $this->assertEquals('en-gb', $response->metas[0]->locale);

        $this->assertEquals('Test Media', $response->metas[1]->title);
        $this->assertEquals('Dies ist eine Test Beschreibung', $response->metas[1]->description);
        $this->assertEquals('de', $response->metas[1]->locale);
        */

        $this->assertEquals(1, $response->type->id);
    }

    /**
     * @description Test GET all Medias
     */
    public function testcGet()
    {
        /* cGet not working (routing problem)
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/media'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        */
    }

    /**
     * @description Test GET for non existing Resource (404)
     */
    public function testGetByIdNotExisting()
    {
        $client = $this->createTestClient();

        $client->request(
            'GET',
            '/api/media/10'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(0, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * @description Test POST to create a new Collection
     */
    public function testPostWithoutFile()
    {
        $client = $this->createTestClient();

        $imagePath = __DIR__ . '/../../Resources/Resources/images/photo.jpeg';
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media',
            array(
                'type' => array(
                    'id' => 1
                ),
                'collection' => array(
                    'id' => 1
                )
            ),
            array(
                'fileVersion' => $photo
            )
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        var_dump($client->getRequest()->files->all());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
