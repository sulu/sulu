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

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    public function testConfig(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->jsonRequest('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $mediaConfig = $response->sulu_media;

        $this->assertEquals('/redirect/media/:id', $mediaConfig->endpoints->image_format);
    }

    public function testCollectionMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/collection_details');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertSnapshot('collection_details.json', $client->getResponse()->getContent() ?: '');
    }

    public function testMediaMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/media_details');

        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertSnapshot('media_details.json', $client->getResponse()->getContent() ?: '');
    }

    public function testImagesFormMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/page?webspace=sulu_io');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent(), true);

        $schema = $response['types']['images']['schema'] ?? [];

        $this->assertArrayHasKey('required', $schema);
        $this->assertSame(['title', 'url'], $schema['required']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('images', $schema['properties']);
        $this->assertArrayHasKey('image', $schema['properties']);

        $this->assertSnapshot('form_image.json', \json_encode($schema['properties']['image'], \JSON_THROW_ON_ERROR));
        $this->assertSnapshot('form_images.json', \json_encode($schema['properties']['images'], \JSON_THROW_ON_ERROR));
    }
}
