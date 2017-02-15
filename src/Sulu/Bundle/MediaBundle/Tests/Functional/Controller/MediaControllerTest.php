<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

use DateTime;
use Doctrine\ORM\EntityManager;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CollectionType
     */
    private $collectionType;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var CollectionMeta
     */
    private $collectionMeta;

    /**
     * @var MediaType
     */
    private $documentType;

    /**
     * @var MediaType
     */
    private $imageType;

    /**
     * @var MediaType
     */
    private $videoType;

    /**
     * @var MediaType
     */
    private $audioType;

    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var CategoryInterface
     */
    private $category2;

    /**
     * @var string
     */
    protected $mediaDefaultTitle = 'photo';

    /**
     * @var string
     */
    protected $mediaDefaultDescription = 'description';

    protected function setUp()
    {
        parent::setUp();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->cleanImage();
        $this->setUpCollection();
        $this->setUpCategory();
        $this->setUpMedia();
    }

    protected function cleanImage()
    {
        if ($this->getContainer()) { //
            $configPath = $this->getContainer()->getParameter('sulu_media.media.storage.local.path');
            $this->recursiveRemoveDirectory($configPath);

            $cachePath = $this->getContainer()->getParameter('sulu_media.format_cache.path');
            $this->recursiveRemoveDirectory($cachePath);
        }
    }

    public function recursiveRemoveDirectory($directory, $counter = 0)
    {
        foreach (glob($directory . '/*') as $file) {
            if (is_dir($file)) {
                $this->recursiveRemoveDirectory($file, $counter + 1);
            } elseif (file_exists($file)) {
                unlink($file);
            }
        }

        if ($counter != 0) {
            rmdir($directory);
        }
    }

    /**
     * set up two categories.
     */
    private function setUpCategory()
    {
        /* First Category
        -------------------------------------*/
        $category = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category->setKey('first-category-key');
        $category->setDefaultLocale('en');

        $this->category = $category;

        // name for first category
        $categoryTrans = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans->setLocale('en');
        $categoryTrans->setTranslation('First Category');
        $categoryTrans->setCategory($category);
        $category->addTranslation($categoryTrans);

        // meta for first category
        $categoryMeta = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta->setLocale('en');
        $categoryMeta->setKey('description');
        $categoryMeta->setValue('Description of Category');
        $categoryMeta->setCategory($category);
        $category->addMeta($categoryMeta);

        /* Second Category
        -------------------------------------*/
        $category2 = $this->getContainer()->get('sulu.repository.category')->createNew();
        $category2->setKey('second-category-key');
        $category2->setDefaultLocale('de');

        $this->category2 = $category2;

        // name for second category
        $categoryTrans2 = $this->getContainer()->get('sulu.repository.category_translation')->createNew();
        $categoryTrans2->setLocale('de');
        $categoryTrans2->setTranslation('Second Category');
        $categoryTrans2->setCategory($category2);
        $category2->addTranslation($categoryTrans2);

        // meta for second category
        $categoryMeta2 = $this->getContainer()->get('sulu.repository.category_meta')->createNew();
        $categoryMeta2->setLocale('de');
        $categoryMeta2->setKey('description');
        $categoryMeta2->setValue('Description of second Category');
        $categoryMeta2->setCategory($category2);
        $category2->addMeta($categoryMeta2);

        $this->em->persist($category2);
        $this->em->persist($category);

        $this->em->flush();
    }

    protected function setUpMedia()
    {
        // Create Media Type
        $this->documentType = new MediaType();
        $this->documentType->setName('document');
        $this->documentType->setDescription('This is a document');

        $this->imageType = new MediaType();
        $this->imageType->setName('image');
        $this->imageType->setDescription('This is an image');

        $this->videoType = new MediaType();
        $this->videoType->setName('video');
        $this->videoType->setDescription('This is a video');

        $this->audioType = new MediaType();
        $this->audioType->setName('audio');
        $this->audioType->setDescription('This is a video');

        // create some tags
        $tag1 = new Tag();
        $tag1->setName('Tag 1');

        $tag2 = new Tag();
        $tag2->setName('Tag 2');

        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($this->documentType);
        $this->em->persist($this->audioType);
        $this->em->persist($this->imageType);
        $this->em->persist($this->videoType);

        $this->em->flush();
    }

    protected function createMedia($name, $locale = 'en-gb', $type = 'image')
    {
        $media = new Media();

        if ($type === 'image') {
            $media->setType($this->imageType);
            $extension = 'jpeg';
            $mimeType = 'image/jpg';
        } elseif ($type === 'audio') {
            $media->setType($this->audioType);
            $extension = 'mp3';
            $mimeType = 'audio/mp3';
        } elseif ($type === 'video') {
            $media->setType($this->videoType);
            $extension = 'mp4';
            $mimeType = 'video/mp4';
        } else {
            $media->setType($this->documentType);
            $extension = 'txt';
            $mimeType = 'text/plain';
        }

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.' . $extension);
        $fileVersion->setMimeType($mimeType);
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->addCategory($this->category);
        $fileVersion->addCategory($this->category2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $fileVersion->setStorageOptions('{"segment":"1","fileName":"' . $name . '.' . $extension . '"}');
        if (!file_exists(__DIR__ . '/../../uploads/media/1')) {
            mkdir(__DIR__ . '/../../uploads/media/1', 0777, true);
        }
        copy($this->getImagePath(), __DIR__ . '/../../uploads/media/1/' . $name . '.' . $extension);

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale($locale);
        $fileVersionMeta->setTitle($name);
        $fileVersionMeta->setDescription($this->mediaDefaultDescription);
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $media->addFile($file);
        $media->setCollection($this->collection);

        $this->em->persist($media);
        $this->em->persist($file);
        $this->em->persist($fileVersionMeta);
        $this->em->persist($fileVersion);

        $this->em->flush();

        return $media;
    }

    protected function setUpCollection()
    {
        $this->collection = new Collection();
        $style = [
            'type' => 'circle', 'color' => '#ffcc00',
        ];

        $this->collection->setStyle(json_encode($style));

        // Create Collection Type
        $this->collectionType = new CollectionType();
        $this->collectionType->setName('Default Collection Type');
        $this->collectionType->setDescription('Default Collection Type');

        $this->collection->setType($this->collectionType);

        // Collection Meta 1
        $this->collectionMeta = new CollectionMeta();
        $this->collectionMeta->setTitle('Test Collection');
        $this->collectionMeta->setDescription('This Description is only for testing');
        $this->collectionMeta->setLocale('en-gb');
        $this->collectionMeta->setCollection($this->collection);

        $this->collection->addMeta($this->collectionMeta);

        // Collection Meta 2
        $collectionMeta2 = new CollectionMeta();
        $collectionMeta2->setTitle('Test Kollektion');
        $collectionMeta2->setDescription('Dies ist eine Test Beschreibung');
        $collectionMeta2->setLocale('de');
        $collectionMeta2->setCollection($this->collection);

        $this->collection->addMeta($collectionMeta2);

        $this->em->persist($this->collection);
        $this->em->persist($this->collectionType);
        $this->em->persist($this->collectionMeta);
        $this->em->persist($collectionMeta2);
    }

    /**
     * Test Media DownloadCounter.
     */
    public function testResponseHeader()
    {
        $media = $this->createMedia('photo');
        $date = new DateTime();
        $date->modify('+1 month');
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/uploads/media/sulu-50x50/01/' . $media->getId() . '-photo.jpeg'
        );
        $this->assertEquals(
            $date->format('Y-m-d'),
            $client->getResponse()->getExpires()->format('Y-m-d')
        );
    }

    /**
     * Test Media DownloadCounter.
     */
    public function test404ResponseHeader()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/uploads/media/50x50/01/0-photo.jpeg'
        );
        $this->assertFalse($client->getResponse()->isCacheable());
        $this->assertEmpty($client->getResponse()->getExpires());
    }

    /**
     * Test Header dispositionType attachment.
     */
    public function testDownloadHeaderAttachment()
    {
        $media = $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();
        ob_start();
        $client->request(
            'GET',
            '/media/' . $media->getId() . '/download/photo.jpeg'
        );
        ob_end_clean();
        $this->assertEquals(
            'attachment; filename="photo.jpeg"',
            $client->getResponse()->headers->get('Content-Disposition')
        );
    }

    /**
     * Test Header dispositionType inline.
     */
    public function testDownloadHeaderInline()
    {
        $media = $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();
        ob_start();
        $client->request(
            'GET',
            '/media/' . $media->getId() . '/download/photo.jpeg?inline=1'
        );
        ob_end_clean();
        $this->assertEquals(
            'inline; filename="photo.jpeg"',
            $client->getResponse()->headers->get('Content-Disposition')
        );
    }

    /**
     * Test Header dispositionType umlauts in name.
     */
    public function testDownloadHeaderUmlauts()
    {
        $media = $this->createMedia('wöchentlich');
        $client = $this->createAuthenticatedClient();
        ob_start();
        $client->request(
            'GET',
            '/media/' . $media->getId() . '/download/wöchentlich.jpeg?inline=1'
        );
        ob_end_clean();
        $this->assertEquals(
            'inline; filename="woechentlich.jpeg"; filename*=utf-8\'\'w%C3%B6chentlich.jpeg',
            $client->getResponse()->headers->get('Content-Disposition')
        );
    }

    /**
     * Test Media GET by ID.
     */
    public function testGetById()
    {
        $media = $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/' . $media->getId() . '?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($media->getId(), $response['id']);
        $this->assertNotNull($response['type']['id']);
        $this->assertEquals('image', $response['type']['name']);
        $this->assertEquals('en-gb', $response['locale']);
        $this->assertArrayNotHasKey('fallbackLocale', $response);
        $this->assertEquals('photo.jpeg', $response['name']);
        $this->assertEquals($this->mediaDefaultTitle, $response['title']);
        $this->assertEquals('2', $response['downloadCounter']);
        $this->assertEquals($this->mediaDefaultDescription, $response['description']);
        $this->assertNotEmpty($response['url']);
        $this->assertNotEmpty($response['thumbnails']);

        $categories = [
            [
                'id' => $response['categories'][0]['id'],
                'key' => $response['categories'][0]['key'],
                'name' => $response['categories'][0]['name'],
            ],
            [
                'id' => $response['categories'][1]['id'],
                'key' => $response['categories'][1]['key'],
                'name' => $response['categories'][1]['name'],
            ],
        ];

        $this->assertContains([
            'id' => $this->category->getId(),
            'key' => $this->category->getKey(),
            'name' => 'First Category',
        ], $categories);
        $this->assertContains([
            'id' => $this->category2->getId(),
            'key' => $this->category2->getKey(),
            'name' => 'Second Category',
        ], $categories);
    }

    /**
     * Test Media GET by ID, without passing a locale.
     */
    public function testGetByIdWithNoLocale()
    {
        $media = $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/' . $media->getId()
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testGetByIdWithFallback()
    {
        $media = $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/media/' . $media->getId() . '?locale=de');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($media->getId(), $response['id']);
        $this->assertEquals('de', $response['locale']);
        $this->assertEquals('en-gb', $response['fallbackLocale']);
    }

    /**
     * Test GET all Media.
     */
    public function testCget()
    {
        $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);
        $this->assertEquals(1, $response->total);
    }

    /**
     * Test GET all Media, without passing a locale.
     */
    public function testCgetWithNoLocale()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/media'
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    /**
     * Test GET all Media.
     */
    public function testCgetCollection()
    {
        $media = $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    /**
     * Test GET all Media.
     */
    public function testcGetCollectionTypes()
    {
        $media = $this->createMedia('photo');
        $document = $this->createMedia('document', 'en-gb', 'document');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId() . '&types=image'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    /**
     * Test GET all Media.
     */
    public function testcGetCollectionTypesNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId() . '&types=audio'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(0, $response->total);
    }

    /**
     * Test GET all Media.
     */
    public function testcGetCollectionTypesMultiple()
    {
        $media = $this->createMedia('photo');
        $audio = $this->createMedia('audio', 'en-gb', 'audio');
        $document = $this->createMedia('document', 'en-gb', 'document');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb&collection=' . $this->collection->getId() . '&types=image,audio&sortBy=id'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(2, $response->total);

        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
        $this->assertEquals($audio->getId(), $response->_embedded->media[1]->id);
        $this->assertEquals('audio.mp3', $response->_embedded->media[1]->name);
    }

    public function testcGetFallbacks()
    {
        $mediaEN = $this->createMedia('test-en', 'en');
        $mediaDE = $this->createMedia('test-de', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=de'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $this->assertNotEmpty($response);

        $medias = array_map(
            function ($item) {
                return ['id' => $item->id, 'name' => $item->name, 'title' => $item->title, 'locale' => $item->locale];
            },
            $response->_embedded->media
        );

        $this->assertEquals(2, $response->total);
        $this->assertCount(2, $medias);
        $this->assertContains(
            ['id' => $mediaEN->getId(), 'name' => 'test-en.jpeg', 'title' => 'test-en', 'locale' => 'en'],
            $medias
        );
        $this->assertContains(
            ['id' => $mediaDE->getId(), 'name' => 'test-de.jpeg', 'title' => 'test-de', 'locale' => 'de'],
            $medias
        );
    }

    /**
     * Test GET all Media.
     */
    public function testcGetIds()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb&ids=' . $media->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(1, $response->total);
        $this->assertEquals($media->getId(), $response->_embedded->media[0]->id);
        $this->assertEquals('photo.jpeg', $response->_embedded->media[0]->name);
    }

    public function testcGetMultipleIds()
    {
        $media1 = $this->createMedia('photo1');
        $media2 = $this->createMedia('photo2');
        $this->createMedia('photo3');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb&ids=' . $media2->getId() . ',' . $media1->getId()
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $medias = array_map(
            function ($item) {
                return ['id' => $item->id, 'name' => $item->name];
            },
            $response->_embedded->media
        );

        $this->assertEquals(2, $response->total);
        $this->assertCount(2, $medias);

        $this->assertEquals(['id' => $media1->getId(), 'name' => 'photo1.jpeg'], $medias[1]);
        $this->assertEquals(['id' => $media2->getId(), 'name' => 'photo2.jpeg'], $medias[0]);
    }

    public function testCgetSearch()
    {
        $this->createMedia('photo1');
        $this->createMedia('photo2');
        $this->createMedia('picture3');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/media?locale=en-gb&searchFields=title&search=photo%2A'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertCount(2, $response['_embedded']['media']);

        $titles = array_map(
            function ($media) {
                return $media['name'];
            },
            $response['_embedded']['media']
        );

        $this->assertContains('photo2.jpeg', $titles);
        $this->assertContains('photo1.jpeg', $titles);
    }

    /**
     * Test GET all Media.
     */
    public function testcGetNotExistingIds()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media?locale=en-gb&ids=1232,3123,1234'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response);

        $this->assertEquals(0, $response->total);
    }

    /**
     * Test GET for non existing Resource (404).
     */
    public function testGetByIdNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/11230?locale=en-gb'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * Test POST to create a new Media with details.
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
            [
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'New Image Title',
                'description' => 'New Image Description',
                'copyright' => 'My copyright',
                'credits' => 'My credits',
                'contentLanguages' => [
                    'en-gb',
                ],
                'focusPointX' => 0,
                'focusPointY' => 0,
                'publishLanguages' => [
                    'en-gb',
                    'en-au',
                    'en',
                    'de',
                ],
                'categories' => [
                    $this->category->getId(), $this->category2->getId(),
                ],
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertNotNull($response->id);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertEquals('New Image Title', $response->title);
        $this->assertEquals('New Image Description', $response->description);
        $this->assertEquals('My copyright', $response->copyright);
        $this->assertEquals('My credits', $response->credits);
        $this->assertEquals(0, $response->focusPointX);
        $this->assertEquals(0, $response->focusPointY);
        $this->assertNotEmpty($response->url);
        $this->assertNotEmpty($response->thumbnails);
        $this->assertEquals(
            [
                'en-gb',
            ],
            $response->contentLanguages
        );
        $this->assertEquals(
            [
                'en-gb',
                'en-au',
                'en',
                'de',
            ],
            $response->publishLanguages
        );

        $categories = [
            [
                'id' => $response->categories[0]->id,
                'key' => $response->categories[0]->key,
                'name' => $response->categories[0]->name,
            ],
            [
                'id' => $response->categories[1]->id,
                'key' => $response->categories[1]->key,
                'name' => $response->categories[1]->name,
            ],
        ];

        $this->assertContains([
            'id' => $this->category->getId(),
            'key' => $this->category->getKey(),
            'name' => 'First Category',
        ], $categories);
        $this->assertContains([
            'id' => $this->category2->getId(),
            'key' => $this->category2->getKey(),
            'name' => 'Second Category',
        ], $categories);
    }

    /**
     * Test POST to create a new Media without details.
     *
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
            [
                'locale' => 'en',
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->mediaDefaultTitle, $response->title);

        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertNotNull($response->id);
    }

    /**
     * Test POST to create a new Media without details.
     *
     * @group postWithoutDetails
     */
    public function testPostWithoutExtension()
    {
        $client = $this->createAuthenticatedClient();

        $imagePath = $this->getImagePath();
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media',
            [
                'locale' => 'en',
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->mediaDefaultTitle, $response->title);

        $this->assertEquals('photo', $response->name);
        $this->assertNotNull($response->id);
    }

    /**
     * Test POST to create a new Media without details.
     *
     * @group postWithoutDetails
     */
    public function testPostWithSmallFile()
    {
        $client = $this->createAuthenticatedClient();

        $filePath = $this->getFilePath();
        $this->assertTrue(file_exists($filePath));
        $photo = new UploadedFile($filePath, 'small.txt', 'text/plain', 0);

        $client->request(
            'POST',
            '/api/media',
            [
                'locale' => 'en',
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('small', $response->title);

        $this->assertEquals('small.txt', $response->name);
        $this->assertNotNull($response->id);
    }

    /**
     * Test PUT to create a new FileVersion.
     */
    public function testFileVersionUpdate()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();

        $imagePath = $this->getImagePath();
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media/' . $media->getId() . '?action=new-version',
            [
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'New Image Title',
                'description' => 'New Image Description',
                'copyright' => 'My copyright',
                'credits' => 'My credits',
                'contentLanguages' => [
                    'en-gb',
                ],
                'publishLanguages' => [
                    'en-gb',
                    'en-au',
                    'en',
                    'de',
                ],
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(2, $response->version);
        $this->assertCount(2, (array) $response->versions);
        $this->assertNotEquals($response->versions->{'2'}->created, $response->versions->{'1'}->created);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('New Image Title', $response->title);
        $this->assertEquals('New Image Description', $response->description);
        $this->assertEquals('My copyright', $response->copyright);
        $this->assertEquals('My credits', $response->credits);
        $this->assertEquals(
            [
                'en-gb',
            ],
            $response->contentLanguages
        );
        $this->assertEquals(
            [
                'en-gb',
                'en-au',
                'en',
                'de',
            ],
            $response->publishLanguages
        );
    }

    /**
     * Test PUT to create a new FileVersion.
     */
    public function testPutWithoutFile()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/media/' . $media->getId(),
            [
                'collection' => $this->collection->getId(),
                'locale' => 'en-gb',
                'title' => 'Update Title',
                'description' => 'Update Description',
                'copyright' => 'My copyright',
                'credits' => 'My credits',
                'focusPointX' => 1,
                'focusPointY' => 2,
                'contentLanguages' => [
                    'en-gb',
                ],
                'publishLanguages' => [
                    'en-gb',
                    'en-au',
                    'en',
                    'de',
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(1, $response->version);
        $this->assertCount(1, (array) $response->versions);
        $this->assertEquals('en-gb', $response->locale);
        $this->assertEquals('Update Title', $response->title);
        $this->assertEquals('Update Description', $response->description);
        $this->assertEquals('My copyright', $response->copyright);
        $this->assertEquals('My credits', $response->credits);
        $this->assertEquals(1, $response->focusPointX);
        $this->assertEquals(2, $response->focusPointY);
        $this->assertNotEmpty($response->url);
        $this->assertNotEmpty($response->thumbnails);
        $this->assertEquals(
            [
                'en-gb',
            ],
            $response->contentLanguages
        );
        $this->assertEquals(
            [
                'en-gb',
                'en-au',
                'en',
                'de',
            ],
            $response->publishLanguages
        );
    }

    /**
     * Test PUT to create a new FileVersion.
     */
    public function testFileVersionUpdateWithoutDetails()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();

        $imagePath = $this->getImagePath();
        $this->assertTrue(file_exists($imagePath));
        $photo = new UploadedFile($imagePath, 'photo.jpeg', 'image/jpeg', 160768);

        $client->request(
            'POST',
            '/api/media/' . $media->getId() . '?action=new-version',
            [
                'collection' => $this->collection->getId(),
            ],
            [
                'fileVersion' => $photo,
            ]
        );

        $this->assertEquals(1, count($client->getRequest()->files->all()));

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals($media->getId(), $response->id);
        $this->assertEquals($this->mediaDefaultTitle, $response->title);
        $this->assertEquals($this->mediaDefaultDescription, $response->description);
        $this->assertEquals($this->collection->getId(), $response->collection);
        $this->assertEquals(2, $response->version);
        $this->assertCount(2, (array) $response->versions);
        $this->assertNotEmpty($response->url);
        $this->assertNotEmpty($response->thumbnails);
    }

    /**
     * Test DELETE.
     */
    public function testDeleteById()
    {
        $media = $this->createMedia('photo');
        $this->assertFileExists(__DIR__ . '/../../uploads/media/1/photo.jpeg');

        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/media/' . $media->getId());
        $this->assertNotNull($client->getResponse()->getStatusCode());

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/' . $media->getId() . '?locale=en-gb'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertTrue(isset($response->message));

        $this->assertFileNotExists(__DIR__ . '/../../uploads/media/1/photo.jpeg');
    }

    /**
     * Test DELETE Collection.
     */
    public function testDeleteCollection()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/collections/' . $this->collection->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/media/' . $media->getId() . '?locale=en-gb'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5015, $response->code);
        $this->assertTrue(isset($response->message));
    }

    /**
     * Test DELETE on none existing Object.
     */
    public function testDeleteByIdNotExisting()
    {
        $media = $this->createMedia('photo');
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/media/404');
        $this->assertHttpStatusCode(404, $client->getResponse());

        $client->request('GET', '/api/media?locale=en-gb');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(1, $response->total);
    }

    /**
     * Test Media DownloadCounter.
     */
    public function testDownloadCounter()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();

        ob_start();
        $client->request(
            'GET',
            '/media/' . $media->getId() . '/download/photo.jpeg'
        );
        ob_end_clean();

        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/media/' . $media->getId() . '?locale=en-gb'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($media->getId(), $response->id);
        $this->assertNotNull($response->type->id);
        $this->assertEquals('image', $response->type->name);
        $this->assertEquals('photo.jpeg', $response->name);
        $this->assertEquals($this->mediaDefaultTitle, $response->title);
        $this->assertEquals('3', $response->downloadCounter);
        $this->assertEquals($this->mediaDefaultDescription, $response->description);
    }

    /**
     * Test move action.
     */
    public function testMove()
    {
        $destCollection = new Collection();
        $style = [
            'type' => 'circle',
            'color' => '#ffcc00',
        ];

        $destCollection->setStyle(json_encode($style));
        $destCollection->setType($this->collectionType);
        $destCollection->addMeta($this->collectionMeta);

        $this->em->persist($destCollection);
        $this->em->flush();

        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/media/' . $media->getId() . '?locale=en-gb&action=move&destination=' . $destCollection->getId()
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($destCollection->getId(), $response['collection']);
        $this->assertEquals($this->mediaDefaultTitle, $response['title']);
    }

    /**
     * Test move action, without passing a locale.
     */
    public function testMoveWithNoLocale()
    {
        $destCollection = new Collection();
        $style = [
            'type' => 'circle',
            'color' => '#ffcc00',
        ];

        $destCollection->setStyle(json_encode($style));
        $destCollection->setType($this->collectionType);
        $destCollection->addMeta($this->collectionMeta);

        $this->em->persist($destCollection);
        $this->em->flush();

        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/media/' . $media->getId() . '?action=move&destination=' . $destCollection->getId()
        );

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    /**
     * Test move to non existing collection.
     */
    public function testMoveNonExistingCollection()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/media/' . $media->getId() . '?action=move&destination=404');

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    /**
     * Test move to non existing media.
     */
    public function testMoveNonExistingMedia()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/media/404?locale=en-gb&action=move&destination=' . $this->collection->getId());

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    /**
     * Test non existing action.
     */
    public function testMoveNonExistingAction()
    {
        $media = $this->createMedia('photo');

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/media/' . $media->getId() . '?action=test');

        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../app/Resources/images/photo.jpeg';
    }

    /**
     * @return string
     */
    private function getFilePath()
    {
        return __DIR__ . '/../../app/Resources/files/small.txt';
    }
}
