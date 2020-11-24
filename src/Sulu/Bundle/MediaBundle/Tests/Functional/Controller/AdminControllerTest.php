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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    public function testContactsConfig()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/config');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $mediaConfig = $response->sulu_media;

        $this->assertEquals('/redirect/media/:id', $mediaConfig->endpoints->image_format);
    }

    public function testCollectionMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/collection_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('title', $form);
        $this->assertObjectHasAttribute('description', $form);

        $schema = $response->schema;

        $this->assertEquals(['title'], $schema->required);
    }

    public function testMediaMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/media_details');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;
        $this->assertObjectHasAttribute('media_upload', $form);
        $this->assertObjectHasAttribute('media_details', $form);

        $items = $form->media_details->items;
        $this->assertObjectHasAttribute('title', $items);
        $this->assertObjectHasAttribute('description', $items);
        $this->assertObjectHasAttribute('license', $items);
        $this->assertObjectHasAttribute('taxonomies', $items);

        $schema = $response->schema;

        $this->assertEquals(['title'], $schema->required);
    }

    public function testImagesFormMetadataAction()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/metadata/form/page?webspace=sulu_io');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = \json_decode($client->getResponse()->getContent());

        $metadata = $response->types->images;
        $schema = (array) $metadata->schema;

        $this->assertArrayHasKey('required', $schema);
        $this->assertSame(['title', 'url', 'images'], $schema['required']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('images', $schema['properties']);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'ids' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'number',
                    ],
                    'minItems' => 1,
                    'uniqueItems' => true,
                ],
                'displayOption' => [
                    'type' => 'string',
                ],
            ],
            'required' => [
                'ids',
            ],
        ], $schema['properties']['images']);
        $this->assertArrayHasKey('image', $schema['properties']);
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'number',
                ],
                'displayOption' => [
                    'type' => 'string',
                ],
            ],
        ], $schema['properties']['image']);
    }
}
