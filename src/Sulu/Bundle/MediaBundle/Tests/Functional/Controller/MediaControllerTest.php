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
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class MediaControllerTest extends SuluTestCase
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Media
     */
    protected $media;

    protected function setUp()
    {
        parent::setUp();
        $this->purgeDatabase();
        $this->em = $this->db('ORM')->getOm();
        $this->cleanImage();
        $this->setUpMedia();
    }


    protected function cleanImage()
    {
        if (self::$kernel->getContainer()) { //
            $configPath = self::$kernel->getContainer()->getParameter('sulu_media.media.storage.local.path');
            $this->recursiveRemoveDirectory($configPath);
        }
    }

    function recursiveRemoveDirectory($directory, $counter = 0)
    {
        foreach(glob($directory . '/*') as $file) {
            if (is_dir($file)) {
                $this->recursiveRemoveDirectory($file, $counter + 1);
            } elseif(file_exists($file)) {
                unlink($file);
            }
        }

        if ($counter != 0) {
            rmdir($directory);
        }
    }

    protected function setUpMedia()
    {
        // Media
        $media = new Media();

        $media->setCreated(new DateTime());
        $media->setChanged(new DateTime());
        $this->media = $media;

        // Create Media Type
        $mediaType = new MediaType();
        $mediaType->setName('document');
        $mediaType->setDescription('This is a document');

        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');

        $videoType = new MediaType();
        $videoType->setName('video');
        $videoType->setDescription('This is a video');

        $videoType = new MediaType();
        $videoType->setName('audio');
        $videoType->setDescription('This is an audio');

        $media->setType($imageType);

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setCreated(new DateTime());
        $file->setChanged(new DateTime());
        $file->setMedia($media);

        // create some tags
        $tag1 = new Tag();
        $tag1->setCreated(new DateTime());
        $tag1->setChanged(new DateTime());
        $tag1->setName('Tag 1');

        $tag2 = new Tag();
        $tag2->setCreated(new DateTime());
        $tag2->setChanged(new DateTime());
        $tag2->setName('Tag 2');

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setCreated(new DateTime());
        $fileVersion->setChanged(new DateTime());
        $fileVersion->setName('photo.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setStorageOptions('{"segment":"1","fileName":"photo.jpeg"}');
        mkdir(__DIR__ . '/../../uploads/media/1', 0777, true);
        copy($this->getImagePath(), __DIR__ . '/../../uploads/media/1/photo.jpeg');

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale('en-gb');
        $fileVersionMeta->setTitle('photo');
        $fileVersionMeta->setDescription('description');
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $media->addFile($file);

        // Setup Collection
        $collection = new Collection();

        $this->setUpCollection($collection);

        $media->setCollection($collection);

        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($media);
        $this->em->persist($file);
        $this->em->persist($fileVersionMeta);
        $this->em->persist($fileVersion);
        $this->em->persist($mediaType);
        $this->em->persist($imageType);
        $this->em->persist($videoType);

        $this->em->flush();
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
        $this->collection = $collection;

        // Create Collection Type
        $collectionType = new CollectionType();
        $collectionType->setName('Default Collection Type');
        $collectionType->setDescription('Default Collection Type');
        $this->collectionType = $collectionType;

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
    }

    /**
     * @description Test Media GET by ID
     */
    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/' . $this->media->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($this->media->getId(), $response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals('image', $response->type->name);
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertEquals('photo', $response->title);
        $this->assertEquals('2', $response->downloadCounter);
        $this->assertEquals('description', $response->description);
    }

    /**
     * @description Test GET all Media
     */
    public function testcGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
    }

    /**
     * @description Test GET all Media
     */
    public function testcGetCollection()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?collection=' . $this->collection->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    /**
     * @description Test GET all Media
     */
    public function testcGetCollectionTypes()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?collection=' . $this->collection->getId() . '&types=image'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    /**
     * @description Test GET all Media
     */
    public function testcGetCollectionTypesNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?collection=' . $this->collection->getId() .'&types=audio'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(0, $response->total);
    }

    /**
     * @description Test GET all Media
     */
    public function testcGetCollectionTypesMultiple()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?collection=' . $this->collection->getId(). '&types=image,audio'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    /**
     * @description Test GET all Media
     */
    public function testcGetIds()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?ids=' . $this->media->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($this->media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    /**
     * @description Test GET all Media
     */
    public function testcGetNotExistingIds()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?ids=1232,3123,1234'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(0, $response->total);
    }

    /**
     * @description Test GET for non existing Resource (404)
     */
    public function testGetByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/11230'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * @description Test POST to create a new Media with details
     */
    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $imagePath = $this->getImagePath();
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media',
            array(
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'New Image Title',
                'description' => 'New Image Description',
                'contentLanguages' => array(
                    'en-gb'
                ),
                'publishLanguages' => array(
                    'en-gb',
                    'en-au',
                    'en',
                    'de'
                ),
            ),
            array(
                'fileVersion' => $photo
            )
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertNotNull($response->id);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertEquals('New Image Title', $response->title);
        $this->assertEquals('New Image Description', $response->description);

        $this->assertEquals(array(
            'en-gb'
        ), $response->contentLanguages);

        $this->assertEquals(array(
            'en-gb',
            'en-au',
            'en',
            'de'
        ), $response->publishLanguages);
    }

    /**
     * @description Test POST to create a new Media without details
     * @group postWithoutDetails
     */
    public function testPostWithoutDetails()
    {
        $client = $this->createAuthenticatedClient();

        $imagePath = $this->getImagePath();
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media',
            array(
                'collection' => $this->collection->getId(),
            ),
            array(
                'fileVersion' => $photo
            )
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('photo', $response->title);

        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertNotNull($response->id);
    }

    /**
     * @description Test PUT to create a new FileVersion
     */
    public function testFileVersionUpdate()
    {
        $client = $this->createAuthenticatedClient();

        $imagePath = $this->getImagePath();
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media/' . $this->media->getId(),
            array(
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'New Image Title',
                'description' => 'New Image Description',
                'contentLanguages' => array(
                    'en-gb'
                ),
                'publishLanguages' => array(
                    'en-gb',
                    'en-au',
                    'en',
                    'de'
                ),
            ),
            array(
                'fileVersion' => $photo
            )
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals($this->media->getId(), $response->id);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(2, $response->version);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('New Image Title', $response->title);
        $this->assertEquals('New Image Description', $response->description);
        $this->assertEquals(array(
            'en-gb'
        ), $response->contentLanguages);
        $this->assertEquals(array(
            'en-gb',
            'en-au',
            'en',
            'de'
        ), $response->publishLanguages);
    }

    /**
     * @description Test PUT to create a new FileVersion
     */
    public function testPutWithoutFile()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/media/' . $this->media->getId(),
            array(
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'Update Title',
                'description' => 'Update Description',
                'contentLanguages' => array(
                    'en-gb'
                ),
                'publishLanguages' => array(
                    'en-gb',
                    'en-au',
                    'en',
                    'de'
                ),
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals($this->media->getId(), $response->id);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(1, $response->version);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('Update Title', $response->title);
        $this->assertEquals('Update Description', $response->description);
        $this->assertEquals(array(
            'en-gb'
        ), $response->contentLanguages);
        $this->assertEquals(array(
            'en-gb',
            'en-au',
            'en',
            'de'
        ), $response->publishLanguages);
    }

    /**
     * @description Test PUT to create a new FileVersion
     */
    public function testFileVersionUpdateWithoutDetails()
    {
        $client = $this->createAuthenticatedClient();

        $imagePath = $this->getImagePath();
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media/' . $this->media->getId(),
            array(
                'collection' => $this->collection->getId()
            ),
            array(
                'fileVersion' => $photo
            )
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals($this->media->getId(), $response->id);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(2, $response->version);
    }

    /**
     * @description Test DELETE
     */
    public function testDeleteById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/media/' . $this->media->getId());
        $this->assertNotNull($client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/' .  $this->media->getId()
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * @description Test DELETE Collection
     */
    public function testDeleteCollection()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/collections/' . $this->collection->getId());
        $this->assertEquals('204', $client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/' . $this->media->getId()
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * @description Test DELETE on none existing Object
     */
    public function testDeleteByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/media/404');
        $this->assertEquals('404', $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/media');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    /**
     * @description Test Media DownloadCounter
     */
    public function testDownloadCounter()
    {
        $client = $this->createAuthenticatedClient();

        ob_start();
        $client->request(
            'GET',
            '/media/' . $this->media->getId() . '/download/photo.jpeg'
        );
        ob_end_clean();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request(
            'GET',
            '/api/media/' . $this->media->getId()
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($this->media->getId(), $response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals('image', $response->type->name);
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertEquals('photo', $response->title);
        $this->assertEquals('3', $response->downloadCounter);
        $this->assertEquals('description', $response->description);
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../app/Resources/images/photo.jpeg';
    }
}
