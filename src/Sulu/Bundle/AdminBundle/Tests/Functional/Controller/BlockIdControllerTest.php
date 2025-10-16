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
        $this->client->jsonRequest('GET', '/admin/api/block-ids.json');

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
}
