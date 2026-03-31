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

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\MarkupBundle\Markup\Link\ExternalLinkProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class PageLinkRedirectTest extends SuluTestCase
{
    use CreatePageTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
        self::purgeDatabase();
    }

    public function testInternalLinkedPageRedirectsToTargetPage(): void
    {
        $targetPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Link Target Page',
                    'url' => '/link-target',
                    'template' => 'default',
                ],
            ],
        ]);

        self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Internal Link Page',
                    'url' => '/internal-link',
                    'template' => 'default',
                    'linkOn' => true,
                    'linkData' => [
                        'href' => $targetPage->getUuid(),
                        'provider' => PageLinkProvider::ALIAS,
                    ],
                ],
            ],
        ]);

        self::getEntityManager()->clear();

        $this->client->request('GET', 'http://sulu.io/en/internal-link');

        $response = $this->client->getResponse();

        $this->assertSame(301, $response->getStatusCode(), 'Unexpected response: ' . ($response->getContent() ?: ''));
        $this->assertSame('http://sulu.io/en/link-target', $response->headers->get('Location'));
    }

    public function testExternalLinkedPageRedirectsToExternalUrl(): void
    {
        self::createPage([
            'en' => [
                'live' => [
                    'title' => 'External Link Page',
                    'url' => '/external-link',
                    'template' => 'default',
                    'linkOn' => true,
                    'linkData' => [
                        'href' => 'https://example.com/target',
                        'provider' => ExternalLinkProvider::ALIAS,
                    ],
                ],
            ],
        ]);

        self::getEntityManager()->clear();

        $this->client->request('GET', 'http://sulu.io/en/external-link');

        $response = $this->client->getResponse();

        $this->assertSame(301, $response->getStatusCode(), 'Unexpected response: ' . ($response->getContent() ?: ''));
        $this->assertSame('https://example.com/target', $response->headers->get('Location'));
    }
}
