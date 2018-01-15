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

use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaStreamControllerTest extends SuluTestCase
{
    public function setUp()
    {
        parent::setUp();

        $collectionTypes = new LoadCollectionTypes();
        $collectionTypes->load($this->getEntityManager());
        $mediaTypes = new LoadMediaTypes();
        $mediaTypes->load($this->getEntityManager());
    }

    public function testDownloadAction()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'test.jpg');
        $media = $this->createMedia($filePath, 'file-without-extension');
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $media->getUrl());
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testDownloadWithoutExtensionAction()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'file-without-extension');
        $media = $this->createMedia($filePath, 'File without Extension');
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $media->getUrl());
        $response = $client->getResponse();
        $this->assertHttpStatusCode(200, $response);
    }

    public function testGetImageActionForNonExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/uploads/media/sulu-400x400/01/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDownloadActionForNonExistingMedia()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/media/999/download/test.jpg?v=1');

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    private function createUploadedFile($path)
    {
        return new UploadedFile($path, basename($path), mime_content_type($path), filesize($path));
    }

    private function createCollection($title = 'Test')
    {
        $collection = $this->getCollectionManager()->save(
            [
                'title' => $title,
                'locale' => 'en',
                'type' => ['id' => 1],
            ],
            1
        );

        return $collection->getId();
    }

    private function createMedia($path, $title)
    {
        return $this->getMediaManager()->save(
            $this->createUploadedFile($path),
            [
                'title' => $title,
                'collection' => $this->createCollection(),
                'locale' => 'en',
            ],
            null
        );
    }

    private function getMediaManager()
    {
        return $this->getContainer()->get('sulu_media.media_manager');
    }

    private function getCollectionManager()
    {
        return $this->getContainer()->get('sulu_media.collection_manager');
    }
}
