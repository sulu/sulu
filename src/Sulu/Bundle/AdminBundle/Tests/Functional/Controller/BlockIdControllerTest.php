<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class BlockIdControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
    }

    public function testGenerate(): void
    {
        $this->client->jsonRequest('POST', '/admin/api/block-ids.json');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = $this->client->getResponse()->getContent();
        $this->assertIsString($content);

        $response = \json_decode($content, true);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertIsString($response['id']);
        $this->assertNotEmpty($response['id']);
        $this->assertSame(8, \strlen($response['id']));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}$/', $response['id']);
    }

    public function testGenerateBatch(): void
    {
        $this->client->jsonRequest('POST', '/admin/api/block-ids.json?length=3');

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $content = $this->client->getResponse()->getContent();
        $this->assertIsString($content);

        $response = \json_decode($content, true);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_embedded', $response);
        $this->assertIsArray($response['_embedded']);
        $this->assertArrayHasKey('blockIds', $response['_embedded']);

        $blockIds = $response['_embedded']['blockIds'];
        $this->assertIsArray($blockIds);
        $this->assertCount(3, $blockIds);

        foreach ($blockIds as $blockId) {
            $this->assertIsArray($blockId);
            $this->assertArrayHasKey('id', $blockId);
            $this->assertIsString($blockId['id']);
            $this->assertNotEmpty($blockId['id']);
            $this->assertSame(8, \strlen($blockId['id']));
            $this->assertMatchesRegularExpression('/^[a-f0-9]{8}$/', $blockId['id']);
        }

        // Verify all IDs are unique
        $ids = [];
        foreach ($blockIds as $blockId) {
            $this->assertIsArray($blockId);
            $this->assertArrayHasKey('id', $blockId);
            $ids[] = $blockId['id'];
        }

        $this->assertCount(\count($ids), \array_unique($ids));
    }
}
