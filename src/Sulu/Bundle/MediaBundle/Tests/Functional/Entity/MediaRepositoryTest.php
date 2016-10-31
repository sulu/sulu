<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FormatOptions;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaRepository;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;

class MediaRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Collection[]
     */
    private $collections;

    /**
     * @var MediaType[]
     */
    private $mediaTypes = [];

    /**
     * @var MediaRepository
     */
    private $mediaRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->setUpCollections();
        $this->setUpMedia();

        $this->mediaRepository = $this->getContainer()->get('sulu.repository.media');
    }

    protected function setUpMedia()
    {
        // Create Media Type
        $documentType = new MediaType();
        $documentType->setName('document');
        $documentType->setDescription('This is a document');

        $imageType = new MediaType();
        $imageType->setName('image');
        $imageType->setDescription('This is an image');

        $videoType = new MediaType();
        $videoType->setName('video');
        $videoType->setDescription('This is a video');

        $this->mediaTypes['image'] = $imageType;
        $this->mediaTypes['video'] = $videoType;

        // create some tags
        $tag1 = new Tag();
        $tag1->setName('Tag 1');

        $tag2 = new Tag();
        $tag2->setName('Tag 2');

        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($documentType);
        $this->em->persist($imageType);
        $this->em->persist($videoType);

        $this->em->flush();
    }

    protected function setUpCollections()
    {
        // Create Collection Type
        $defaultCollectionType = new CollectionType();
        $defaultCollectionType->setName('Default Collection Type');
        $defaultCollectionType->setKey('collection.default');
        $defaultCollectionType->setDescription('Default Collection Type');

        $systemCollectionType = new CollectionType();
        $systemCollectionType->setName('System Collection Type');
        $systemCollectionType->setKey(SystemCollectionManagerInterface::COLLECTION_TYPE);
        $systemCollectionType->setDescription('System Collection Type');

        $this->em->persist($defaultCollectionType);
        $this->em->persist($systemCollectionType);

        $collection = new Collection();
        $collection->setType($defaultCollectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        $this->em->persist($collection);
        $this->em->persist($defaultCollectionType);
        $this->em->persist($collectionMeta);

        $this->collections[] = $collection;

        $collection = new Collection();
        $collection->setType($defaultCollectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        $this->em->persist($collection);
        $this->em->persist($collectionMeta);

        $this->collections[] = $collection;

        $collection = new Collection();
        $collection->setType($systemCollectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test System Collection');
        $collectionMeta->setDescription('This Description is only for testing');
        $collectionMeta->setLocale('en-gb');
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        $this->em->persist($collection);
        $this->em->persist($collectionMeta);

        $this->collections[] = $collection;
    }

    protected function createMedia($name, $title, $type = 'image', $collection = 0)
    {
        $media = new Media();
        $media->setType($this->mediaTypes[$type]);

        // create file
        $file = new File();
        $file->setVersion(1);
        $file->setMedia($media);

        // create file version
        $fileVersion = new FileVersion();
        $fileVersion->setVersion(1);
        $fileVersion->setName($name . '.jpeg');
        $fileVersion->setMimeType('image/jpg');
        $fileVersion->setFile($file);
        $fileVersion->setSize(1124214);
        $fileVersion->setDownloadCounter(2);
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $fileVersion->setStorageOptions('{"segment":"1","fileName":"' . $name . '.jpeg"}');
        if (!file_exists(__DIR__ . '/../../uploads/media/1')) {
            mkdir(__DIR__ . '/../../uploads/media/1', 0777, true);
        }
        copy($this->getImagePath(), __DIR__ . '/../../uploads/media/1/' . $name . '.jpeg');

        // create meta
        $fileVersionMeta = new FileVersionMeta();
        $fileVersionMeta->setLocale('en-gb');
        $fileVersionMeta->setTitle($title);
        $fileVersionMeta->setDescription('decription');
        $fileVersionMeta->setFileVersion($fileVersion);

        $fileVersion->addMeta($fileVersionMeta);
        $fileVersion->setDefaultMeta($fileVersionMeta);

        $file->addFileVersion($fileVersion);

        $formatOptions = new FormatOptions();
        $formatOptions->setFormatKey('my-format');
        $formatOptions->setFileVersion($fileVersion);
        $formatOptions->setCropHeight(10);
        $formatOptions->setCropWidth(20);
        $formatOptions->setCropX(30);
        $formatOptions->setCropY(40);
        $fileVersion->addFormatOptions($formatOptions);

        $media->addFile($file);
        $media->setCollection($this->collections[$collection]);

        $this->em->persist($media);
        $this->em->persist($file);
        $this->em->persist($fileVersionMeta);
        $this->em->persist($formatOptions);
        $this->em->persist($fileVersion);

        $this->em->flush();

        return $media;
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../app/Resources/images/photo.jpeg';
    }

    public function testFindMedia()
    {
        $media1 = $this->createMedia('test-1', 'test-1');
        $media2 = $this->createMedia('test-2', 'test-2');
        $media3 = $this->createMedia('test-3', 'test-3');
        $media4 = $this->createMedia('test-4', 'test-4');

        $result = $this->mediaRepository->findMedia();

        $this->assertCount(4, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());
        $this->assertEquals($media4->getId(), $result[3]->getId());
    }

    public function testFindMediaPagination()
    {
        $media1 = $this->createMedia('test-1', 'test-1');
        $media2 = $this->createMedia('test-2', 'test-2');
        $media3 = $this->createMedia('test-3', 'test-3');
        $media4 = $this->createMedia('test-4', 'test-4');

        $result = $this->mediaRepository->findMedia([], 3, 0);

        $this->assertCount(3, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());

        $result = $this->mediaRepository->findMedia([], 3, 3);
        $this->assertCount(1, $result);
        $this->assertEquals($media4->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia([], 3, 6);
        $this->assertCount(0, $result);

        $this->assertEquals(4, $this->mediaRepository->count([]));
    }

    public function testFindMediaSearch()
    {
        $media1 = $this->createMedia('test-1', 'A');
        $media2 = $this->createMedia('test-2', 'AA');
        $media3 = $this->createMedia('test-3', 'AAA');
        $media4 = $this->createMedia('test-4', 'AB');

        $result = $this->mediaRepository->findMedia(['search' => 'AA']);

        $this->assertCount(2, $result);
        $this->assertEquals($media2->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());

        $this->assertEquals(2, $this->mediaRepository->count(['search' => 'AA']));
    }

    public function testFindMediaSearchPagination()
    {
        $media1 = $this->createMedia('test-1', 'A');
        $media2 = $this->createMedia('test-2', 'AA');
        $media3 = $this->createMedia('test-3', 'AAA');
        $media4 = $this->createMedia('test-4', 'AAAA');

        $result = $this->mediaRepository->findMedia(['search' => 'AA'], 2, 0);

        $this->assertCount(2, $result);
        $this->assertEquals($media2->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['search' => 'AA'], 2, 2);

        $this->assertCount(1, $result);
        $this->assertEquals($media4->getId(), $result[0]->getId());

        $this->assertEquals(3, $this->mediaRepository->count(['search' => 'AA']));
    }

    public function testFindMediaTypes()
    {
        $media1 = $this->createMedia('test-1', 'test-1', 'video');
        $media2 = $this->createMedia('test-2', 'test-2', 'image');
        $media3 = $this->createMedia('test-3', 'test-3', 'video');
        $media4 = $this->createMedia('test-4', 'test-4', 'image');

        $result = $this->mediaRepository->findMedia(['types' => ['video']]);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['image']]);

        $this->assertCount(2, $result);
        $this->assertEquals($media2->getId(), $result[0]->getId());
        $this->assertEquals($media4->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['image', 'video']]);

        $this->assertCount(4, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());
        $this->assertEquals($media4->getId(), $result[3]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['asdf']]);

        $this->assertCount(0, $result);

        $this->assertEquals(2, $this->mediaRepository->count(['types' => ['image']]));
        $this->assertEquals(2, $this->mediaRepository->count(['types' => ['video']]));
        $this->assertEquals(4, $this->mediaRepository->count(['types' => ['image', 'video']]));
        $this->assertEquals(0, $this->mediaRepository->count(['types' => ['asdf']]));
    }

    public function testFindMediaTypesPagination()
    {
        $media1 = $this->createMedia('test-1', 'test-1', 'video');
        $media2 = $this->createMedia('test-2', 'test-2', 'video');
        $media3 = $this->createMedia('test-3', 'test-3', 'video');
        $media4 = $this->createMedia('test-4', 'test-4', 'image');

        $result = $this->mediaRepository->findMedia(['types' => ['video']], 2, 0);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['video']], 2, 2);

        $this->assertCount(1, $result);
        $this->assertEquals($media3->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia(['types' => ['video']], 2, 4);

        $this->assertCount(0, $result);

        $this->assertEquals(3, $this->mediaRepository->count(['types' => ['video']]));
    }

    public function testFindMediaByCollection()
    {
        $media1 = $this->createMedia('test-1', 'test-1', 'image', 1);
        $media2 = $this->createMedia('test-2', 'test-2', 'image', 1);
        $media3 = $this->createMedia('test-3', 'test-3', 'image', 1);
        $media4 = $this->createMedia('test-4', 'test-4', 'image', 0);

        $result = $this->mediaRepository->findMedia(['collection' => $this->collections[1]->getId()]);

        $this->assertCount(3, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media2->getId(), $result[1]->getId());
        $this->assertEquals($media3->getId(), $result[2]->getId());

        $result = $this->mediaRepository->findMedia(['collection' => $this->collections[0]->getId()]);

        $this->assertCount(1, $result);
        $this->assertEquals($media4->getId(), $result[0]->getId());

        $this->assertEquals(3, $this->mediaRepository->count(['collection' => $this->collections[1]->getId()]));
        $this->assertEquals(1, $this->mediaRepository->count(['collection' => $this->collections[0]->getId()]));
    }

    public function testFindMediaByCollectionPagination()
    {
        $media1 = $this->createMedia('test-1', 'test-1', 'image', 1);
        $media2 = $this->createMedia('test-2', 'test-2', 'image', 1);
        $media3 = $this->createMedia('test-3', 'test-3', 'image', 1);
        $media4 = $this->createMedia('test-4', 'test-4', 'image', 0);

        $result = $this->mediaRepository->findMedia(['collection' => $this->collections[1]->getId()], 2, 0);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia(['collection' => $this->collections[1]->getId()], 2, 2);

        $this->assertCount(1, $result);
        $this->assertEquals($media3->getId(), $result[0]->getId());

        $result = $this->mediaRepository->findMedia(['collection' => $this->collections[1]->getId()], 2, 4);

        $this->assertCount(0, $result);

        $this->assertEquals(3, $this->mediaRepository->count(['collection' => $this->collections[1]->getId()]));
        $this->assertEquals(1, $this->mediaRepository->count(['collection' => $this->collections[0]->getId()]));
    }

    public function testFindMediaWithSystemCollections()
    {
        $this->createMedia('test-1', 'test-1');
        $this->createMedia('test-2', 'test-2', 'image', 2);

        $this->assertCount(2, $this->mediaRepository->findMedia());
        $this->assertCount(1, $this->mediaRepository->findMedia(['systemCollections' => false]));
    }

    public function testFindMediaWithSystemCollectionsAndTypes()
    {
        $this->createMedia('test-1', 'test-1');
        $this->createMedia('test-2', 'test-2', 'image', 2);

        $this->assertCount(2, $this->mediaRepository->findMedia());
        $this->assertCount(1, $this->mediaRepository->findMedia(['systemCollections' => false, 'types' => ['image']]));
    }

    public function testFindMediaByIds()
    {
        $media1 = $this->createMedia('test-1', 'test-1', 'video');
        $media2 = $this->createMedia('test-2', 'test-2', 'video');
        $media3 = $this->createMedia('test-3', 'test-3', 'video');
        $media4 = $this->createMedia('test-4', 'test-4', 'image');

        $result = $this->mediaRepository->findMedia(['ids' => [$media1->getId(), $media3->getId()]]);

        $this->assertCount(2, $result);
        $this->assertEquals($media1->getId(), $result[0]->getId());
        $this->assertEquals($media3->getId(), $result[1]->getId());
    }

    public function testFindMediaDisplayInfo()
    {
        $media1 = $this->createMedia('test-1', 'test-1-title', 'image');
        $media2 = $this->createMedia('test-2', 'test-2-title', 'image');
        $media3 = $this->createMedia('test-3', 'test-3-title', 'video');
        $media4 = $this->createMedia('test-4', 'test-4-title', 'video');

        $result = $this->mediaRepository->findMediaDisplayInfo([$media1->getId(), $media3->getId()], 'en-gb');
        $this->assertEquals(2, count($result));
        $this->assertEquals(5, count($result[0]));
        $this->assertEquals(5, count($result[1]));
        $this->assertEquals($media1->getId(), $result[0]['id']);
        $this->assertEquals($media1->getFiles()[0]->getVersion(), $result[0]['version']);
        $this->assertEquals('test-1.jpeg', $result[0]['name']);
        $this->assertEquals('test-1-title', $result[0]['title']);
        $this->assertEquals('test-1-title', $result[0]['defaultTitle']);
    }

    public function testFindMediaDisplayInfoWithIncorrectIds()
    {
        $media1 = $this->createMedia('test-1', 'test-1-title', 'image');
        $media2 = $this->createMedia('test-2', 'test-2-title', 'image');
        $media3 = $this->createMedia('test-3', 'test-3-title', 'video');
        $media4 = $this->createMedia('test-4', 'test-4-title', 'video');

        $result = $this->mediaRepository->findMediaDisplayInfo([-1], 'en-gb');

        $this->assertNotNull($result);
        $this->assertEquals(0, count($result));
    }

    public function testCount()
    {
        $this->createMedia('test-1', 'test-1');
        $this->createMedia('test-2', 'test-2', 'image', 2);

        $this->assertEquals(2, $this->mediaRepository->count([]));
        $this->assertEquals(1, $this->mediaRepository->count(['systemCollections' => false]));
    }

    public function testGetMediaByIdForRendering()
    {
        $media = $this->createMedia('media-with-format-options', 'Media with format options');

        $retrievedMedia = $this->mediaRepository->findMediaByIdForRendering($media->getId(), 'my-format');

        $this->assertEquals($media->getId(), $retrievedMedia->getId());
        $this->assertEquals(1, count($retrievedMedia->getFiles()));
        $this->assertEquals(1, count($retrievedMedia->getFiles()->get(0)->getFileVersions()));

        /** @var FileVersion $fileVersion */
        $fileVersion = $retrievedMedia->getFiles()->get(0)->getFileVersions()->get(0);

        $this->assertEquals(1, count($fileVersion->getFormatOptions()));

        /** @var FormatOptions $formatOptions */
        $formatOptions = $fileVersion->getFormatOptions()->get('my-format');

        $this->assertEquals('my-format', $formatOptions->getFormatKey());
        $this->assertEquals($fileVersion->getId(), $formatOptions->getFileVersion()->getId());
        $this->assertEquals(10, $formatOptions->getCropHeight());
        $this->assertEquals(20, $formatOptions->getCropWidth());
        $this->assertEquals(30, $formatOptions->getCropX());
        $this->assertEquals(40, $formatOptions->getCropY());
    }
}
