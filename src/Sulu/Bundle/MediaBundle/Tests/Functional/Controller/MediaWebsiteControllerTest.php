<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Controller;

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
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class MediaWebsiteControllerTest extends WebsiteTestCase
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
    private $imageType;

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

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();
        $this->cleanImage();
        $this->setUpCollection();
        $this->setUpMedia();
    }

    protected function cleanImage()
    {
        if ($this->getContainer()) {
            $configPath = $this->getContainer()->getParameter('sulu_media.media.storage.local.path');
            $this->recursiveRemoveDirectory($configPath);

            $cachePath = $this->getContainer()->getParameter('sulu_media.format_cache.path');
            $this->recursiveRemoveDirectory($cachePath);
        }
    }

    public function recursiveRemoveDirectory($directory, $counter = 0): void
    {
        foreach (\glob($directory . '/*') as $file) {
            if (\is_dir($file)) {
                $this->recursiveRemoveDirectory($file, $counter + 1);
            } elseif (\file_exists($file)) {
                \unlink($file);
            }
        }

        if (0 != $counter) {
            \rmdir($directory);
        }
    }

    protected function setUpMedia()
    {
        $this->imageType = new MediaType();
        $this->imageType->setName('image');
        $this->imageType->setDescription('This is an image');

        $this->em->persist($this->imageType);

        $this->em->flush();
    }

    protected function createMedia($name, $locale = 'en-gb', $type = 'image')
    {
        $media = new Media();

        if ('image' === $type) {
            $media->setType($this->imageType);
            $extension = 'jpeg';
            $mimeType = 'image/jpg';
        } elseif ('audio' === $type) {
            $media->setType($this->audioType);
            $extension = 'mp3';
            $mimeType = 'audio/mp3';
        } elseif ('video' === $type) {
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
        $fileVersion->setChanged(new \DateTime('1937-04-20'));
        $fileVersion->setCreated(new \DateTime('1937-04-20'));
        $fileVersion->setStorageOptions(['segment' => '1', 'fileName' => $name . '.' . $extension]);
        $storagePath = $this->getStoragePath();

        if (!\file_exists($storagePath . '/1')) {
            \mkdir($storagePath . '/1', 0777, true);
        }
        \copy($this->getImagePath(), $storagePath . '/1/' . $name . '.' . $extension);

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

        $this->collection->setStyle(\json_encode($style) ?: null);

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

        $this->em->persist($this->collection);
        $this->em->persist($this->collectionType);
        $this->em->persist($this->collectionMeta);
    }

    /**
     * Test Media DownloadCounter.
     */
    public function testResponseHeader(): void
    {
        $media = $this->createMedia('photo');
        $date = new \DateTime();
        $date->modify('+1 month');

        $this->client->request(
            'GET',
            '/uploads/media/sulu-50x50/01/' . $media->getId() . '-photo.jpeg'
        );

        $expiresDate = new \DateTime($this->client->getResponse()->headers->get('Expires'));
        $expiresDate->modify('+1 second');
        $this->assertGreaterThanOrEqual(new \DateTime(), $expiresDate);
    }

    private function getStoragePath()
    {
        return $this->getContainer()->getParameter('sulu_media.media.storage.local.path');
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../Fixtures/files/photo.jpeg';
    }
}
