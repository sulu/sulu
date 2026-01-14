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

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Page\Infrastructure\Sulu\Content\PageTeaserProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Component\Routing\RequestContext;

#[CoversClass(PageTeaserProvider::class)]
class PageTeaserProviderTest extends WebsiteTestCase
{
    use CreateMediaTrait;
    use CreatePageTrait;
    use SetGetPrivatePropertyTrait;

    private PageTeaserProvider $teaserProvider;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->teaserProvider = self::getContainer()->get('sulu_page.page_teaser_provider');

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'sulu-io');
    }

    public function testGetConfiguration(): void
    {
        $configuration = $this->teaserProvider->getConfiguration();

        $this->assertSame('pages', self::getPrivateProperty($configuration, 'resourceKey'));
        $this->assertSame('column_list', self::getPrivateProperty($configuration, 'listAdapter'));
        $this->assertSame(['title'], self::getPrivateProperty($configuration, 'displayProperties'));
        $this->assertNull(self::getPrivateProperty($configuration, 'view'));
        $this->assertNull(self::getPrivateProperty($configuration, 'resultToView'));
    }

    public function testFindReturnsEmptyArrayForEmptyIds(): void
    {
        $result = $this->teaserProvider->find([], 'en');

        $this->assertSame([], $result);
    }

    public function testFindReturnsTeasersWithExcerptData(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Test Page Title',
                    'url' => '/test-page',
                    'template' => 'default',
                    'excerpt' => [
                        'title' => 'Excerpt Title',
                        'description' => 'Excerpt description text',
                        'more' => 'Read more',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $teaser = $teasers[0];

        $this->assertSame($page->getUuid(), $teaser->getId());
        $this->assertSame('pages', $teaser->getType());
        $this->assertSame('en', $teaser->getLocale());
        $this->assertSame('Excerpt Title', $teaser->getTitle());
        $this->assertSame('Excerpt description text', $teaser->getDescription());
        $this->assertSame('Read more', $teaser->getMoreText());
        $this->assertStringEndsWith('/test-page', $teaser->getUrl());
    }

    public function testFindReturnsEmptyTitleWhenNoExcerptTitle(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Page Without Excerpt Title',
                    'url' => '/no-excerpt-title',
                    'template' => 'default',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('', $teasers[0]->getTitle());
    }

    public function testFindReturnsTeaserWithMediaId(): void
    {
        $collection = self::createCollection();
        $media = self::createMedia($collection, ['title' => 'Teaser Image']);
        self::getEntityManager()->flush();

        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Page With Excerpt Image',
                    'url' => '/with-excerpt-image',
                    'template' => 'default',
                    'excerpt' => [
                        'title' => 'Excerpt With Image',
                        'image' => ['id' => $media->getId()],
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame($media->getId(), $teasers[0]->getMediaId());
    }

    public function testFindSkipsUnpublishedPages(): void
    {
        $page = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Draft Only Page',
                    'url' => '/draft-page',
                    'template' => 'default',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(0, $teasers);
    }

    public function testFindSkipsPagesWithoutMatchingLocale(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'English Only Page',
                    'url' => '/english-only',
                    'template' => 'default',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'de');

        $this->assertCount(0, $teasers);
    }

    public function testFindPreservesOrder(): void
    {
        $page1 = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'First Page',
                    'url' => '/first',
                    'template' => 'default',
                    'excerpt' => ['title' => 'First'],
                ],
            ],
        ]);

        $page2 = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Second Page',
                    'url' => '/second',
                    'template' => 'default',
                    'excerpt' => ['title' => 'Second'],
                ],
            ],
        ]);

        $page3 = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Third Page',
                    'url' => '/third',
                    'template' => 'default',
                    'excerpt' => ['title' => 'Third'],
                ],
            ],
        ]);

        // Request in reverse order
        $teasers = $this->teaserProvider->find([
            $page3->getUuid(),
            $page1->getUuid(),
            $page2->getUuid(),
        ], 'en');

        $this->assertCount(3, $teasers);
        $this->assertSame($page3->getUuid(), $teasers[0]->getId());
        $this->assertSame($page1->getUuid(), $teasers[1]->getId());
        $this->assertSame($page2->getUuid(), $teasers[2]->getId());
    }

    public function testFindWithExternalLink(): void
    {
        $page = self::createPage([
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
                    'excerpt' => [
                        'title' => 'External Link Title',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('https://example.com', $teasers[0]->getUrl());
    }

    public function testFindWithMultipleLocales(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'English Title',
                    'url' => '/english',
                    'template' => 'default',
                    'excerpt' => ['title' => 'EN Excerpt'],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'German Title',
                    'url' => '/deutsch',
                    'template' => 'default',
                    'excerpt' => ['title' => 'DE Excerpt'],
                ],
            ],
        ]);

        $englishTeasers = $this->teaserProvider->find([$page->getUuid()], 'en');
        $germanTeasers = $this->teaserProvider->find([$page->getUuid()], 'de');

        $this->assertCount(1, $englishTeasers);
        $this->assertCount(1, $germanTeasers);
        $this->assertSame('EN Excerpt', $englishTeasers[0]->getTitle());
        $this->assertSame('DE Excerpt', $germanTeasers[0]->getTitle());
        $this->assertStringEndsWith('/english', $englishTeasers[0]->getUrl());
        $this->assertStringEndsWith('/deutsch', $germanTeasers[0]->getUrl());
    }

    public function testFindWithInternalPageLinkUsesTargetExcerpt(): void
    {
        // Target page (the page being linked TO)
        $targetPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Target Page',
                    'url' => '/target-page',
                    'template' => 'default',
                    'excerpt' => [
                        'title' => 'Target Excerpt Title',
                        'description' => 'Target excerpt description',
                    ],
                ],
            ],
        ]);

        // Source page (links to target page internally)
        $sourcePage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Source Page',
                    'url' => '/source-page',
                    'template' => 'default',
                    'linkOn' => true,
                    'linkData' => [
                        'href' => $targetPage->getUuid(),
                        'provider' => 'page',
                    ],
                    'excerpt' => [
                        'title' => 'Source Excerpt Title',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$sourcePage->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        // Should use TARGET page's excerpt, not source's
        $this->assertSame('Target Excerpt Title', $teasers[0]->getTitle());
        $this->assertSame('Target excerpt description', $teasers[0]->getDescription());
        // URL should point to target page
        $this->assertStringEndsWith('/target-page', $teasers[0]->getUrl());
    }

    public function testFindUsesTaggedDescriptionWhenExcerptEmpty(): void
    {
        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Page With Tagged Description',
                    'url' => '/tagged-description',
                    'template' => 'teaser-tagged',
                    'teaser_description' => 'Description from tagged property',
                    'excerpt' => [
                        'title' => 'Excerpt Title Only',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('Description from tagged property', $teasers[0]->getDescription());
    }

    public function testFindUsesTaggedMediaWhenExcerptEmpty(): void
    {
        $collection = self::createCollection();
        $media = self::createMedia($collection, ['title' => 'Tagged Teaser Image']);
        self::getEntityManager()->flush();

        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Page With Tagged Media',
                    'url' => '/tagged-media',
                    'template' => 'teaser-tagged',
                    'teaser_image' => ['id' => $media->getId()],
                    'excerpt' => [
                        'title' => 'Excerpt Title Only',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame($media->getId(), $teasers[0]->getMediaId());
    }

    public function testFindPrefersExcerptOverTaggedProperty(): void
    {
        $collection = self::createCollection();
        $excerptMedia = self::createMedia($collection, ['title' => 'Excerpt Image']);
        $taggedMedia = self::createMedia($collection, ['title' => 'Tagged Image']);
        self::getEntityManager()->flush();

        $page = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Page With Both Excerpt and Tagged',
                    'url' => '/excerpt-priority',
                    'template' => 'teaser-tagged',
                    'teaser_description' => 'Tagged description should be ignored',
                    'teaser_image' => ['id' => $taggedMedia->getId()],
                    'excerpt' => [
                        'title' => 'Excerpt Title',
                        'description' => 'Excerpt description takes priority',
                        'image' => ['id' => $excerptMedia->getId()],
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$page->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('Excerpt description takes priority', $teasers[0]->getDescription());
        $this->assertSame($excerptMedia->getId(), $teasers[0]->getMediaId());
    }
}
