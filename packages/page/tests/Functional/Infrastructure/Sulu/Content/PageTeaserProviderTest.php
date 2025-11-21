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
use Sulu\Page\Infrastructure\Sulu\Content\PageTeaserProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;

#[CoversNothing]
class PageTeaserProviderTest extends SuluTestCase
{
    use CreatePageTrait;

    private PageTeaserProvider $teaserProvider;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->teaserProvider = self::getContainer()->get('sulu_page.page_teaser_provider');
    }

    public function testFindWithContentAndExternalLinks(): void
    {
        $contentPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Content Teaser Page',
                    'url' => '/content-teaser',
                    'template' => 'default',
                    'excerpt' => [
                        'title' => 'Content Excerpt Title',
                        'description' => 'Content excerpt description',
                    ],
                ],
            ],
        ]);

        // Create external link page
        $externalPage = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'External Teaser Link',
                    'url' => '/external-teaser-link',
                    'template' => 'default',
                    'linkOn' => true,
                    'linkData' => [
                        'href' => 'https://teaser-example.com',
                        'provider' => 'external',
                    ],
                    'excerpt' => [
                        'title' => 'External Excerpt Title',
                        'description' => 'External excerpt description',
                    ],
                ],
            ],
        ]);

        // Find teasers
        $teasers = $this->teaserProvider->find(
            [$contentPage->getUuid(), $externalPage->getUuid()],
            'en'
        );

        $this->assertCount(2, $teasers);

        $teasersByUuid = [];
        foreach ($teasers as $teaser) {
            $teasersByUuid[$teaser->getId()] = $teaser;
        }

        // Test internal link - shows own data
        $this->assertArrayHasKey($contentPage->getUuid(), $teasersByUuid);
        $this->assertSame('Content Excerpt Title', $teasersByUuid[$contentPage->getUuid()]->getTitle());
        $this->assertSame('Content excerpt description', $teasersByUuid[$contentPage->getUuid()]->getDescription());
        $this->assertSame('/content-teaser', $teasersByUuid[$contentPage->getUuid()]->getUrl());

        // Test external link - shows external URL with page's own data
        $this->assertArrayHasKey($externalPage->getUuid(), $teasersByUuid);
        $this->assertSame('External Excerpt Title', $teasersByUuid[$externalPage->getUuid()]->getTitle());
        $this->assertSame('External excerpt description', $teasersByUuid[$externalPage->getUuid()]->getDescription());
        $this->assertSame('https://teaser-example.com', $teasersByUuid[$externalPage->getUuid()]->getUrl());
    }
}
