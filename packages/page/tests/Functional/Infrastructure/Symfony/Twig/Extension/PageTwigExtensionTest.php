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

namespace Sulu\Page\Tests\Functional\Infrastructure\Symfony\Twig\Extension;

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\PageTwigExtension;
use Sulu\Page\Tests\Traits\CreatePageTrait;

class PageTwigExtensionTest extends SuluTestCase
{
    use CreateMediaTrait;
    use CreatePageTrait;

    private PageTwigExtension $pageTwigExtension;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->pageTwigExtension = self::getContainer()->get('sulu_page.page_twig_extension');
    }

    public function testLoadPageWithoutProperties(): void
    {
        $collection = self::createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media = self::createMedia($collection, ['title' => 'Test Image', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $page = static::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Test Page',
                    'url' => '/test-page',
                    'description' => 'This is a test page description',
                    'image' => [
                        'id' => $media->getId(),
                    ],
                ],
            ],
        ]);

        $result = $this->pageTwigExtension->loadPage($page->getUuid(), 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);

        /** @var array<string, mixed> $content */
        $content = $result['content'];
        $this->assertArrayHasKey('title', $content);
        $this->assertSame('Test Page', $content['title']);
        $this->assertArrayHasKey('description', $content);
        $this->assertSame('This is a test page description', $content['description']);
        $this->assertArrayHasKey('image', $content);
        $this->assertInstanceOf(Media::class, $content['image']);
        $this->assertSame($media->getId(), $content['image']->getId());
    }

    public function testLoadPageWithProperties(): void
    {
        $collection = self::createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media = self::createMedia($collection, ['title' => 'Test Image', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $page = static::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Test Page with Properties',
                    'url' => '/test-page-props',
                    'description' => 'Description for properties test',
                    'image' => [
                        'id' => $media->getId(),
                    ],
                ],
            ],
        ]);

        $properties = [
            'title' => 'title',
            'description' => 'description',
        ];

        $result = $this->pageTwigExtension->loadPage($page->getUuid(), 'en', $properties);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertSame('Test Page with Properties', $result['title']);
        $this->assertArrayHasKey('description', $result);
        $this->assertSame('Description for properties test', $result['description']);

        if (isset($result['content'])) {
            $this->assertEmpty($result['content']);
        }
        $this->assertArrayNotHasKey('image', $result);
    }

    public function testLoadPageReturnsNullWhenPageNotFound(): void
    {
        $result = $this->pageTwigExtension->loadPage('nonexistent-uuid', 'en');

        $this->assertNull($result);
    }
}
