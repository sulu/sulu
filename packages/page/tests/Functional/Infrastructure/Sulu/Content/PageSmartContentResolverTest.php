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
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;

#[CoversNothing]
class PageSmartContentResolverTest extends SuluTestCase
{
    use CreatePageTrait;

    private ContentResolverInterface $contentResolver;
    private ContentAggregatorInterface $contentAggregator;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentResolver = self::getContainer()->get('sulu_content.content_resolver');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');
    }

    public function testResolveSmartContentWithLinks(): void
    {
        // Create parent/homepage first
        $homepage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Homepage',
                    'url' => '/',
                    'template' => 'default',
                ],
            ],
        ]);

        // Create container page with SmartContent
        $containerPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Container Page',
                    'url' => '/container',
                    'template' => 'page_with_smart_content',
                    'parentId' => $homepage->getId(),
                    'pages' => [
                        'dataSource' => $homepage->getUuid(),
                        'includeSubFolders' => false,
                        'categories' => [],
                        'categoryOperator' => 'OR',
                        'tags' => [],
                        'tagOperator' => 'OR',
                        'types' => ['default'],
                    ],
                ],
            ],
        ]);

        // Create target page for internal link
        $targetPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Target Page',
                    'url' => '/target-page',
                    'template' => 'default',
                    'parentId' => $containerPage->getId(),
                ],
            ],
        ]);

        self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Content Page',
                    'url' => '/content-page',
                    'template' => 'default',
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        // Create internal link page
        self::createPage([
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
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        // Create external link page
        self::createPage([
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
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        $dimensionContent = $this->contentAggregator->aggregate($containerPage, ['locale' => 'en', 'stage' => 'live']);
        $result = $this->contentResolver->resolve($dimensionContent);

        $content = $result['content'];
        $this->assertArrayHasKey('pages', $content);
        /** @var array<int, array<string, mixed>> $pages */
        $pages = $content['pages'];
        $this->assertCount(3, $pages);

        // Find pages by title to avoid ordering issues
        $pagesByTitle = [];
        foreach ($pages as $page) {
            $title = $page['title'];
            $this->assertIsString($title);
            $pagesByTitle[$title] = $page;
        }

        // Test link - shows own title and URL
        $this->assertArrayHasKey('Content Page', $pagesByTitle);
        $this->assertSame('Content Page', $pagesByTitle['Content Page']['title']);
        $this->assertSame('/content-page', $pagesByTitle['Content Page']['url']);

        // Test external link - shows external URL
        $this->assertArrayHasKey('External Link Page', $pagesByTitle);
        $this->assertSame('https://example.com', $pagesByTitle['External Link Page']['url']);
        $this->assertSame('External Link Page', $pagesByTitle['External Link Page']['title']);

        // Test internal link - resolves to target page's URL with custom title
        $this->assertArrayHasKey('Internal Link Page', $pagesByTitle);
        $this->assertSame('/target-page', $pagesByTitle['Internal Link Page']['url']);
        $this->assertSame('Internal Link Page', $pagesByTitle['Internal Link Page']['title']);
    }
}
