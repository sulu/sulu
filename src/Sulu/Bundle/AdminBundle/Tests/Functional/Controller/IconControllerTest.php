<?php

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

class IconControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
    }

    public function testCgetActionIcomoon(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/icons', ['locale' => 'en', 'icon_set' => 'sulu']);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $responseData = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('_embedded', $responseData);
        /** @var mixed[] $embedded */
        $embedded = $responseData['_embedded'];
        $this->assertArrayHasKey('icons', $embedded);

        $icons = $embedded['icons'] ?? [];
        $this->assertIsArray($icons);
        $someIcons = ['video', 'wifi', 'umbrella'];

        $matchingIcons = \array_filter($icons, function($icon) use ($someIcons) {
            /** @var array{id: string, content: string} $icon */
            $icon = $icon ?? [];

            return \in_array($icon['id'], $someIcons) && \str_contains($icon['content'], '<svg');
        });

        $this->assertNotEmpty($matchingIcons, 'No icon with id "test" and content containing "<svg" found');
        $this->assertCount(\count($someIcons), $matchingIcons, 'Not all icons found');
    }

    public function testCgetActionSvgs(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/icons', ['locale' => 'en', 'icon_set' => 'test_svg']);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $responseData = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('_embedded', $responseData);
        /** @var mixed[] $embedded */
        $embedded = $responseData['_embedded'];
        $this->assertArrayHasKey('icons', $embedded);

        $icons = $embedded['icons'] ?? [];
        $this->assertIsArray($icons);
        $svgIds = ['sulu-logo', 'sulu-only'];

        \array_filter($icons, function($icon) use ($svgIds) {
            /** @var array{id: string, content: string} $icon */
            $icon = $icon ?? [];
            $this->assertContains($icon['id'], $svgIds);
            $this->assertStringStartsWith('<svg', $icon['content']);

            return true;
        });
    }

    public function testCgetActionSvgSearch(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/icons', ['locale' => 'en', 'icon_set' => 'test_svg', 'search' => 'only']);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $responseData = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('_embedded', $responseData);
        /** @var mixed[] $embedded */
        $embedded = $responseData['_embedded'];
        $this->assertArrayHasKey('icons', $embedded);

        /** @var array<int, array{id: string, content: string}> $icons */
        $icons = $embedded['icons'] ?? [];

        $this->assertCount(1, $icons);
        $this->assertSame($icons[0]['id'], 'sulu-only');
        $this->assertStringStartsWith('<svg', $icons[0]['content']);
    }
}
