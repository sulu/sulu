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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\DataNormalizer;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\DataNormalizer\ContentViewDataNormalizer;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ContentViewDataNormalizerTest extends TestCase
{
    private ContentViewDataNormalizer $normalizer;
    private PropertyAccessor $propertyAccessor;

    protected function setUp(): void
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->normalizer = new ContentViewDataNormalizer($this->propertyAccessor);
    }

    public function testFormatContentOutput(): void
    {
        // Use real Example entity instead of mock
        $resource = new Example();

        $content = [
            'template' => ['title' => 'Test Title', 'article' => 'Test Article'],
            'settings' => ['seo' => ['title' => 'SEO Title']],
            'extension' => ['data' => 'extension data'],
        ];

        $view = [
            'template' => ['title' => 'Title Field', 'article' => 'Article Field'],
            'settings' => ['seo' => 'SEO Fields'],
        ];

        $result = $this->normalizer->normalizeContentViewData($content, $view, $resource);

        // Test the actual values without redundant PHPStan assertions
        self::assertSame($resource, $result['resource']);
        self::assertSame(['title' => 'Test Title', 'article' => 'Test Article'], $result['content']);
        self::assertSame(['title' => 'Title Field', 'article' => 'Article Field'], $result['view']);
        self::assertSame(['extension' => ['data' => 'extension data']], $result['extension']);

        // The 'seo' key is added dynamically through settings merging
        // PHPStan doesn't know about this dynamic property, so we suppress the warning
        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['title' => 'SEO Title'], $result['seo']);
    }

    public function testFormatContentOutputWithProperties(): void
    {
        // Use real Example entity instead of mock
        $resource = new Example();

        $content = [
            'template' => ['title' => 'Test Title', 'nested' => ['deep' => 'value']],
        ];

        $view = [
            'template' => ['title' => 'Title Field'],
        ];

        $properties = ['nested.deep' => true];

        // The normalizeContentViewData method only takes 3 required parameters
        $result = $this->normalizer->normalizeContentViewData($content, $view, $resource);

        // Then apply property mapping separately if needed
        $this->normalizer->recursivelyMapProperties($result, $properties);

        /** @var array<string, mixed> $resultContent */
        $resultContent = $result['content'];
        self::assertArrayHasKey('nested', $resultContent);

        /** @var array<string, mixed> $nested */
        $nested = $resultContent['nested'];
        self::assertArrayHasKey('deep', $nested);
        self::assertSame('value', $nested['deep']);
    }

    public function testReplaceNestedContentViews(): void
    {
        $formattedContentData = [
            'resource' => new \stdClass(),
            'content' => [
                'items' => [
                    'content' => ['nested' => 'data'],
                    'view' => ['nested' => 'view data'],
                ],
            ],
            'view' => [],
            'extension' => [],
        ];

        $this->normalizer->replaceNestedContentViews($formattedContentData);

        self::assertSame(['nested' => 'data'], $formattedContentData['content']['items']);
        self::assertSame(['nested' => 'view data'], $formattedContentData['view']['items']);
    }

    public function testFormatContentOutputWithEmptyTemplate(): void
    {
        // Use real Example entity instead of mock
        $resource = new Example();

        $content = ['extension' => ['data' => 'extension data']];
        $view = [];

        $result = $this->normalizer->normalizeContentViewData($content, $view, $resource);

        // Test actual values without redundant PHPStan assertions
        self::assertSame([], $result['content']);
        self::assertSame([], $result['view']);
        self::assertSame(['extension' => ['data' => 'extension data']], $result['extension']);
    }

    public function testFormatContentOutputWithNonRootContext(): void
    {
        // Use real Example entity instead of mock
        $resource = new Example();

        $content = [
            'template' => ['title' => 'Test Title'],
        ];

        $view = [
            'template' => ['title' => 'Title Field'],
        ];

        $properties = ['title' => true];

        // The normalizeContentViewData method only takes 3 required parameters
        $result = $this->normalizer->normalizeContentViewData($content, $view, $resource);

        // Apply property mapping with non-root context
        $this->normalizer->recursivelyMapProperties($result, $properties, '', 0, false);

        // When not root, properties should be mapped under [content] path
        self::assertSame('Test Title', $result['content']['title']);
    }
}
