<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Media\Storage;

use League\Flysystem\Filesystem;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\MediaBundle\Tests\Application\RemoteStorageKernel;
use Sulu\Bundle\MediaBundle\Tests\Functional\Mock\InMemoryRemoteStorage;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Runs the media through a storage of the type remote, with flysystem 1.x and 3.x.
 */
class RemoteStorageTest extends WebsiteTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        static::$class = RemoteStorageKernel::class;
        $this->client = $this->createWebsiteClient();
        $this->client->disableReboot();
        $this->purgeDatabase();

        (new LoadCollectionTypes())->load($this->getEntityManager());
        (new LoadMediaTypes())->load($this->getEntityManager());
    }

    protected function tearDown(): void
    {
        static::$class = null;
        parent::tearDown();
    }

    public function testSaveWritesFileToStorage(): void
    {
        $media = $this->createMedia();

        $this->assertTrue($this->getFilesystem()->has($this->getStoragePath($media)));
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDownloadRedirectsToStorage(): void
    {
        $media = $this->createMedia();

        $this->client->request('GET', (string) $media->getUrl());

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(302, $response);
        $this->assertSame(InMemoryRemoteStorage::PUBLIC_URL . '/' . $this->getStoragePath($media), $response->headers->get('Location'));
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testFormatIsCreatedFromStorage(): void
    {
        $media = $this->createMedia();

        $url = $media->getFormats()['small-inset'];
        $this->assertIsString($url);

        $this->client->request('GET', $url);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertSame('image/jpeg', $response->headers->get('Content-Type'));
    }

    public function testDeleteMovesFileToTrash(): void
    {
        $media = $this->createMedia();
        $storagePath = $this->getStoragePath($media);

        $this->getMediaManager()->delete($media->getId());

        $this->assertFalse($this->getFilesystem()->has($storagePath));
        $this->assertTrue($this->getFilesystem()->has('trash/' . $storagePath));
    }

    private function createMedia(): Media
    {
        $path = \sys_get_temp_dir() . '/test.jpg';
        \copy(__DIR__ . '/../../../Fixtures/files/photo.jpeg', $path);

        $collection = $this->getContainer()->get('sulu_media.collection_manager')->save(
            ['title' => 'Test', 'locale' => 'en', 'type' => ['id' => 1]],
            1
        );

        return $this->getMediaManager()->save(
            new UploadedFile($path, 'test.jpg', 'image/jpeg'),
            ['title' => 'Test', 'collection' => $collection->getId(), 'locale' => 'en'],
            null
        );
    }

    private function getStoragePath(Media $media): string
    {
        $storageOptions = $media->getStorageOptions();

        return $storageOptions['segment'] . '/' . $storageOptions['fileName'];
    }

    private function getMediaManager(): MediaManager
    {
        return $this->getContainer()->get('sulu_media.media_manager');
    }

    private function getFilesystem(): Filesystem
    {
        $filesystem = $this->getContainer()->get('sulu_media_test.filesystem');
        $this->assertInstanceOf(Filesystem::class, $filesystem);

        return $filesystem;
    }
}
