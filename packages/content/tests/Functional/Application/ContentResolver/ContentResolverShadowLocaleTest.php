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

namespace Sulu\Content\Tests\Functional\Application\ContentResolver;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Tests\Traits\CreateExampleTrait;
use Sulu\Snippet\Tests\Traits\CreateSnippetTrait;

class ContentResolverShadowLocaleTest extends SuluTestCase
{
    use CreateExampleTrait;
    use CreateSnippetTrait;

    private ContentResolverInterface $contentResolver;
    private ContentAggregatorInterface $contentAggregator;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentResolver = self::getContainer()->get('sulu_content.content_resolver');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');
    }

    public function testResolveSnippetWithShadowLocaleFallback(): void
    {
        $snippet = static::createSnippet([
            'de' => [
                'live' => [
                    'template' => 'snippet-1',
                    'title' => 'Test Snippet DE',
                    'description' => '<p>Snippet description DE</p>',
                ],
            ],
        ]);

        $snippetUuid = $snippet->getUuid();

        $example = static::createExample([
            'de' => [
                'live' => [
                    'template' => 'default-snippet-selection',
                    'title' => 'Test Page DE',
                    'snippet' => $snippetUuid,
                ],
            ],
            'de_li' => [
                'live' => [
                    'template' => 'default-snippet-selection',
                    'title' => 'Test Page DE_LI',
                    'snippet' => $snippetUuid,
                ],
            ],
        ]);

        foreach ($example->getDimensionContents() as $existingDimensionContent) {
            if ('de_li' === $existingDimensionContent->getLocale()) {
                $existingDimensionContent->setShadowLocale('de');
            }
        }

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example, [
            'locale' => 'de_li',
            'stage' => 'live',
        ]);

        $result = $this->contentResolver->resolve($dimensionContent);

        self::assertArrayHasKey('snippet', $result['content']);
        self::assertIsArray($result['content']['snippet']);
        self::assertSame('<p>Snippet description DE</p>', $result['content']['snippet']['description'] ?? null);
    }

    public function testResolveSnippetWithoutShadowLocaleNoFallback(): void
    {
        $snippet = static::createSnippet([
            'de' => [
                'live' => [
                    'template' => 'snippet-1',
                    'title' => 'Test Snippet DE',
                    'description' => '<p>Snippet description DE</p>',
                ],
            ],
        ]);

        $snippetUuid = $snippet->getUuid();

        // de_li page without shadow — snippet doesn't exist in de_li, so it should be empty.
        $example = static::createExample([
            'de_li' => [
                'live' => [
                    'template' => 'default-snippet-selection',
                    'title' => 'Test Page DE_LI',
                    'snippet' => $snippetUuid,
                ],
            ],
        ]);

        static::getEntityManager()->flush();

        $dimensionContent = $this->contentAggregator->aggregate($example, [
            'locale' => 'de_li',
            'stage' => 'live',
        ]);

        $result = $this->contentResolver->resolve($dimensionContent);

        self::assertFalse(\is_array($result['content']['snippet']), 'Snippet should not be resolved without shadow fallback');
    }
}
