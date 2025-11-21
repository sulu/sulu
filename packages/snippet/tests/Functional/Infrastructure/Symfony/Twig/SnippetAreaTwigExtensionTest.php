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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Symfony\Twig;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Sulu\Snippet\Infrastructure\Symfony\Twig\SnippetAreaTwigExtension;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

class SnippetAreaTwigExtensionTest extends SuluTestCase
{
    use CreateMediaTrait;
    use CreateSnippetTrait;

    private SnippetAreaTwigExtension $snippetAreaTwigExtension;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->snippetAreaTwigExtension = self::getContainer()->get('sulu_snippet.snippet_area_twig_extension');
    }

    public function testLoadSnippetByAreaWithoutProperties(): void
    {
        $collection = self::createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media = self::createMedia($collection, ['title' => 'Test Image', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $snippet = static::createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Test Snippet',
                    'description' => 'This is a test snippet description',
                    'image' => [
                        'id' => $media->getId(),
                    ],
                ],
            ],
        ]);
        static::createSnippetArea('hotel', 'sulu-io', $snippet);

        self::getEntityManager()->flush();

        $result = $this->snippetAreaTwigExtension->loadSnippetByArea('hotel', [], 'sulu-io', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);

        /** @var array<string, mixed> $content */
        $content = $result['content'];
        $this->assertEmpty($content);
    }

    public function testLoadSnippetByAreaWithProperties(): void
    {
        $collection = self::createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media = self::createMedia($collection, ['title' => 'Test Image', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $snippet = static::createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Test Snippet with Properties',
                    'description' => 'Description for properties test',
                    'image' => [
                        'id' => $media->getId(),
                    ],
                ],
            ],
        ]);
        static::createSnippetArea('hotel', 'sulu-io', $snippet);

        self::getEntityManager()->flush();

        $properties = [
            'title' => 'title',
            'description' => 'description',
        ];

        $result = $this->snippetAreaTwigExtension->loadSnippetByArea('hotel', $properties, 'sulu-io', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertSame('Test Snippet with Properties', $result['title']);
        $this->assertArrayHasKey('description', $result);
        $this->assertSame('Description for properties test', $result['description']);

        if (isset($result['content'])) {
            $this->assertEmpty($result['content']);
        }
        $this->assertArrayNotHasKey('image', $result);
    }

    public function testLoadSnippetByAreaReturnsNullWhenAreaNotFound(): void
    {
        $result = $this->snippetAreaTwigExtension->loadSnippetByArea('nonexistent', [], 'sulu-io', 'en');

        $this->assertNull($result);
    }

    public function testLoadSnippetByAreaReturnsNullWhenNoSnippetAssigned(): void
    {
        $snippet = static::createSnippet([
            'en' => [
                'live' => [
                    'template' => 'snippet',
                    'title' => 'Test Snippet',
                ],
            ],
        ]);

        self::getEntityManager()->flush();

        $result = $this->snippetAreaTwigExtension->loadSnippetByArea('hotel', [], 'sulu-io', 'en');

        $this->assertNull($result);
    }
}
