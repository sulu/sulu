<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Admin;

use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AdminConfigControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createAuthenticatedClient();
    }

    public function testWebspaceResponse(): void
    {
        $this->client->request('GET', '/admin/config');

        $response = $this->client->getResponse();

        $data = \json_decode($response->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('sulu_page', $data);

        $suluPageData = $data['sulu_page'];

        $this->assertSnapshot('sulu_page_admin_config.json', \json_encode($suluPageData, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT));
    }
}
