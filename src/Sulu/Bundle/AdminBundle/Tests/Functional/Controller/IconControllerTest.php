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

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class IconControllerTest extends SuluTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
    }

    #[DataProvider('dataCgetActionIcomoon')]
    public function testCgetActionIcomoon(string $url): void
    {
        $this->client->jsonRequest('GET', $url);

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $icons = $this->getIconsFromResponse($response);
        $someIcons = ['video', 'wifi', 'umbrella'];

        $matchingIcons = \array_filter($icons, function($icon) use ($someIcons) {
            return \in_array($icon['id'], $someIcons) && \str_contains($icon['content'], '<svg');
        });

        $this->assertNotEmpty($matchingIcons, 'No icon with id "test" and content containing "<svg" found');
        $this->assertCount(\count($someIcons), $matchingIcons, 'Not all icons found');
    }

    /**
     * @return \Generator<string,array{string}>
     */
    public static function dataCgetActionIcomoon(): \Generator
    {
        yield 'specifying an icon set' => ['/admin/api/icons?locale=en&icon_set=sulu'];
        yield 'using default icon set' => ['/admin/api/icons?locale=en'];
    }

    public function testCgetActionSvgs(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/icons?locale=en&icon_set=test_svg');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $icons = $this->getIconsFromResponse($response);
        $svgIds = ['sulu-logo', 'sulu-only'];

        \array_filter($icons, function($icon) use ($svgIds) {
            $this->assertContains($icon['id'], $svgIds);
            $this->assertStringStartsWith('<svg', $icon['content']);

            return true;
        });
    }

    public function testCgetActionSvgSearch(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/icons?locale=en&icon_set=test_svg&search=only');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $icons = $this->getIconsFromResponse($response);

        $this->assertCount(1, $icons);
        $this->assertSame($icons[0]['id'], 'sulu-only');
        $this->assertStringStartsWith('<svg', $icons[0]['content']);
    }

    public function testGettingNonExistingIconSet(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/icons?locale=en&icon_set=does_not_exist&search=only');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    /** @return array<array{id: string, content: string}> */
    private function getIconsFromResponse(Response $response): array
    {
        $responseData = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('_embedded', $responseData);
        /** @var mixed[] $embedded */
        $embedded = $responseData['_embedded'];
        $this->assertArrayHasKey('icons', $embedded);

        $icons = $embedded['icons'] ?? [];
        $this->assertIsArray($icons);

        /** @var array<array{id: string, content: string}> $icons */
        return $icons;
    }
}
