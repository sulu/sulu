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
    public function testContactsConfig(): void
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
        $response = \json_decode($client->getResponse()->getContent());

        $form = $response->form;

        $this->assertObjectHasAttribute('title', $form);
        $this->assertObjectHasAttribute('description', $form);

        $schema = $response->schema;

        $this->assertEquals(['title'], $schema->required);
    }

    public function testMediaMetadataAction(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->jsonRequest('GET', '/admin/metadata/form/media_details');

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

    private function getNullSchema(): array
    {
        return [
            'type' => 'null',
        ];
    }

    private function getEmptyArraySchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null'],
            ],
            'maxItems' => 0,
        ];
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
        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'anyOf' => [
                                $this->getEmptyArraySchema(),
                                [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'number',
                                    ],
                                    'minItems' => 2,
                                    'maxItems' => 3,
                                    'uniqueItems' => true,
                                ],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $schema['properties']['images']);
        $this->assertArrayHasKey('image', $schema['properties']);
        $this->assertEquals([
            'anyOf' => [
                $this->getNullSchema(),
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'anyOf' => [
                                $this->getNullSchema(),
                                ['type' => 'number'],
                            ],
                        ],
                        'displayOption' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $schema['properties']['image']);
    }
}
