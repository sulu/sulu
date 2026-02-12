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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Content;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Component\Routing\RequestContext;

#[CoversNothing]
class PageLinkProviderTest extends SuluTestCase
{
    use CreatePageTrait;

    private PageLinkProvider $linkProvider;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->linkProvider = self::getContainer()->get('sulu_page.page_link_provider');

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'sulu-io');
    }

    public function testPreloadWithContentLinks(): void
    {
        // Create target page for internal link
        $targetPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Link Target Page',
                    'url' => '/link-target',
                    'template' => 'default',
                ],
            ],
        ]);

        $contentPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Content Page',
                    'url' => '/content-page',
                    'template' => 'default',
                ],
            ],
        ]);

        // Create internal link page
        $internalPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Internal Link Page',
                    'url' => '/internal-link',
                    'template' => 'default',
                    'linkOn' => true,
                    'linkData' => [
                        'href' => $targetPage->getUuid(),
                        'provider' => 'page',
                    ],
                ],
            ],
        ]);

        // Create external link page
        $externalPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'External Link Page',
                    'url' => '/external-link',
                    'template' => 'default',
                    'linkOn' => true,
                    'linkData' => [
                        'href' => 'https://example.com',
                        'provider' => 'external',
                    ],
                ],
            ],
        ]);

        // Preload all pages
        $links = $this->linkProvider->preload(
            [$contentPage->getUuid(), $internalPage->getUuid(), $externalPage->getUuid()],
            'en',
            true
        );

        $this->assertCount(3, $links);

        $linksByUuid = [];
        foreach ($links as $link) {
            $linksByUuid[$link->getId()] = $link;
        }

        // Test content link - shows own title and URL resolved via webspace manager
        $this->assertArrayHasKey($contentPage->getUuid(), $linksByUuid);
        $this->assertSame('Content Page', $linksByUuid[$contentPage->getUuid()]->getTitle());
        $this->assertSame('http://sulu.io/en/content-page', $linksByUuid[$contentPage->getUuid()]->getUrl());

        // Test external link - shows external URL with page's own title
        $this->assertArrayHasKey($externalPage->getUuid(), $linksByUuid);
        $this->assertSame('External Link Page', $linksByUuid[$externalPage->getUuid()]->getTitle());
        $this->assertSame('https://example.com', $linksByUuid[$externalPage->getUuid()]->getUrl());

        // Test internal link - resolves to target page's URL with custom title
        $this->assertArrayHasKey($internalPage->getUuid(), $linksByUuid);
        $this->assertSame('Internal Link Page', $linksByUuid[$internalPage->getUuid()]->getTitle());
        $this->assertSame('http://sulu.io/en/link-target', $linksByUuid[$internalPage->getUuid()]->getUrl());
    }
}
