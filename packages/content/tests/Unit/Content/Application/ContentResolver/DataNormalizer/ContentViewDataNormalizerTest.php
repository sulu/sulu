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
    /** @var array<string, list<string>> */
    private const CORE_PATHS = ['template' => ['content'], 'settings' => []];

    private ContentViewDataNormalizer $normalizer;
    private PropertyAccessor $propertyAccessor;

    protected function setUp(): void
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS);
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

    public function testReplaceNestedContentViewsAtEnvelopes(): void
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

        $this->normalizer->replaceNestedContentViewsAtEnvelopes($formattedContentData);

        self::assertSame(['nested' => 'data'], $formattedContentData['content']['items']);
        self::assertSame(['nested' => 'view data'], $formattedContentData['view']['items']);
    }

    public function testReplaceNestedContentViewsAtEnvelopesMergesWithExistingViewData(): void
    {
        // existing outer view keys must win on collision; inner-only keys fill gaps
        $formattedContentData = [
            'resource' => new \stdClass(),
            'content' => [
                'snippet' => [
                    'content' => ['title' => 'Snippet Title'],
                    'view' => [
                        'innerOnly' => 'fromInner',
                        'shared' => 'innerLoses',
                    ],
                ],
            ],
            'view' => [
                'snippet' => [
                    'outerOnly' => 'fromOuter',
                    'shared' => 'outerWins',
                ],
            ],
            'extension' => [],
        ];

        $this->normalizer->replaceNestedContentViewsAtEnvelopes($formattedContentData);

        self::assertSame(['title' => 'Snippet Title'], $formattedContentData['content']['snippet']);

        /** @var array<string, mixed> $mergedSnippetView */
        $mergedSnippetView = $formattedContentData['view']['snippet'];
        self::assertSame('fromOuter', $mergedSnippetView['outerOnly']);
        self::assertSame('fromInner', $mergedSnippetView['innerOnly']);
        self::assertSame('outerWins', $mergedSnippetView['shared']);
    }

    public function testReplaceNestedContentViewsAtEnvelopesCopiesIntoEmptyView(): void
    {
        $formattedContentData = [
            'resource' => new \stdClass(),
            'content' => [
                'snippet' => [
                    'content' => ['title' => 'Snippet Title'],
                    'view' => ['link' => ['provider' => 'page', 'href' => 'uuid-1']],
                ],
            ],
            'view' => [],
            'extension' => [],
        ];

        $this->normalizer->replaceNestedContentViewsAtEnvelopes($formattedContentData);

        self::assertSame(['title' => 'Snippet Title'], $formattedContentData['content']['snippet']);
        self::assertSame(
            ['link' => ['provider' => 'page', 'href' => 'uuid-1']],
            $formattedContentData['view']['snippet']
        );
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
        $this->normalizer->recursivelyMapProperties($result, $properties, [], 0, false);

        // When not root, properties should be mapped under [content] path
        self::assertSame('Test Title', $result['content']['title']);
    }

    public function testRootPathPlacesResolverOutputAtRoot(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['product' => ['product']]);

        $result = $normalizer->normalizeContentViewData(
            ['template' => ['title' => 'T'], 'product' => ['code' => 'NL4FX'], 'seo' => ['title' => 'S']],
            ['template' => [], 'product' => ['code' => 'ignored view']],
            new Example(),
        );

        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['code' => 'NL4FX'], $result['product']);
        self::assertSame(['seo' => ['title' => 'S']], $result['extension']);
        self::assertSame([], $result['view']);
    }

    public function testNestedPathCreatesIntermediateArrays(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['shop' => ['shop', 'meta']]);

        $result = $normalizer->normalizeContentViewData(['template' => [], 'shop' => ['currency' => 'EUR']], [], new Example());

        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['meta' => ['currency' => 'EUR']], $result['shop']);
    }

    public function testUnknownTypeFallsBackToExtension(): void
    {
        $result = $this->normalizer->normalizeContentViewData(['template' => [], 'product' => ['code' => 'X']], [], new Example());

        self::assertSame(['product' => ['code' => 'X']], $result['extension']);
        self::assertArrayNotHasKey('product', $result);
    }

    public function testHigherPriorityResolverWinsOnSharedPath(): void
    {
        // "a" has higher priority and runs first.
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['a' => ['shared'], 'b' => ['shared']]);

        $result = $normalizer->normalizeContentViewData(
            ['template' => [], 'a' => ['k' => 'from-a', 'x' => 1], 'b' => ['k' => 'from-b', 'y' => 2]],
            [],
            new Example(),
        );

        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['k' => 'from-a', 'x' => 1, 'y' => 2], $result['shared']);
    }

    public function testMergeIsShallow(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['a' => [], 'b' => []]);

        $result = $normalizer->normalizeContentViewData(
            ['template' => [], 'a' => ['p' => ['x' => 1]], 'b' => ['p' => ['y' => 2]]],
            [],
            new Example(),
        );

        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['x' => 1], $result['p']);
    }

    public function testRootResolverTargetingAnEnvelopeKeyThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content resolver "settings" cannot be placed at "[root]": it returned the reserved envelope key(s) "content", "view", "extension", "resource".');

        $this->normalizer->normalizeContentViewData(
            ['template' => [], 'settings' => ['content' => 'evil', 'view' => 'evil', 'extension' => 'evil', 'resource' => 'evil', 'author' => 'ok']],
            [],
            new Example(),
        );
    }

    public function testRootResolverKeysThatDoNotClashAreMerged(): void
    {
        $result = $this->normalizer->normalizeContentViewData(
            ['template' => [], 'settings' => ['author' => 'ok', 'template' => 'full-content']],
            [],
            new Example(),
        );

        self::assertSame([], $result['content']);
        self::assertInstanceOf(Example::class, $result['resource']);
        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame('ok', $result['author']);
    }

    public function testNonArrayContentAtRootThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content resolver "settings" cannot be placed at "[root]": it returned string instead of an array.');

        $this->normalizer->normalizeContentViewData(['template' => [], 'settings' => 'scalar'], [], new Example());
    }

    public function testTemplateViewIsMergedOnlyForContentPath(): void
    {
        $result = $this->normalizer->normalizeContentViewData(
            ['template' => ['title' => 'T'], 'seo' => ['title' => 'S']],
            ['template' => ['title' => ['type' => 'text_line']], 'seo' => ['title' => ['type' => 'text_line']]],
            new Example(),
        );

        self::assertSame(['title' => ['type' => 'text_line']], $result['view']);
        self::assertSame(['seo' => ['title' => 'S']], $result['extension']);
    }

    public function testNullFromHigherPriorityResolverThrowsInsteadOfSwallowingTheOther(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['a' => ['shared'], 'b' => ['shared']]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content resolver "b" cannot be placed at "[root][shared]": that slot already holds null and cannot take array.');

        $normalizer->normalizeContentViewData(['template' => [], 'a' => null, 'b' => ['y' => 2]], [], new Example());
    }

    public function testScalarTemplateContentThrowsInsteadOfEmptyingTheEnvelope(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content resolver "template" cannot be placed at "[root][content]": that slot already holds array and cannot take string.');

        $this->normalizer->normalizeContentViewData(['template' => 'scalar'], [], new Example());
    }

    public function testResolverNestedUnderARootResolversKeyThrows(): void
    {
        // The compiler pass cannot see a [root] resolver's keys.
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['acme_meta' => ['template', 'meta']]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Content resolver "acme_meta" cannot be placed at "[root][template]": that slot already holds string.');

        $normalizer->normalizeContentViewData(
            ['settings' => ['template' => 'full-content'], 'template' => [], 'acme_meta' => ['currency' => 'EUR']],
            [],
            new Example(),
        );
    }

    public function testEnvelopePathGetsViewTwin(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['product' => ['product', 'content']]);

        $result = $normalizer->normalizeContentViewData(
            ['template' => [], 'product' => ['code' => 'X']],
            ['product' => ['code' => ['type' => 'text_line']]],
            new Example(),
        );

        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['content' => ['code' => 'X'], 'view' => ['code' => ['type' => 'text_line']]], $result['product']);
    }

    public function testFlatPathStillDropsView(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['product' => ['product']]);

        $result = $normalizer->normalizeContentViewData(
            ['template' => [], 'product' => ['code' => 'X']],
            ['product' => ['code' => ['type' => 'text_line']]],
            new Example(),
        );

        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['code' => 'X'], $result['product']);
        self::assertSame([], $result['view']);
    }

    public function testEnvelopeViewTwinKeepsHigherPriorityKeys(): void
    {
        // "a" has higher priority and runs first.
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['a' => ['shop', 'content'], 'b' => ['shop', 'content']]);

        $result = $normalizer->normalizeContentViewData(
            ['template' => [], 'a' => ['title' => 'A'], 'b' => ['title' => 'B']],
            ['a' => ['k' => 'from-a'], 'b' => ['k' => 'from-b', 'y' => 2]],
            new Example(),
        );

        // @phpstan-ignore-next-line offsetAccess.notFound
        self::assertSame(['k' => 'from-a', 'y' => 2], $result['shop']['view']);
    }

    public function testMergeFieldViewDataIntoItemsSkipsTypesWithoutAContentEnvelope(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['product' => ['product']]);

        $data = [
            'resource' => new Example(),
            'content' => [],
            'view' => [],
            'extension' => [],
            'product' => ['items' => ['id' => 1]],
        ];

        $viewEnhancements = [
            '[product][items]' => [
                'path' => ['product', 'items'],
                'itemsPropertyName' => 'items',
                'items' => [['id' => 1]],
            ],
        ];

        $result = $normalizer->mergeFieldViewDataIntoItems($data, $viewEnhancements);

        // "product" has no sibling view (its path does not end in "content"), so the item view is dropped
        self::assertSame([], $this->propertyAccessor->getValue($result, '[view]'));
        self::assertSame(['id' => 1], $this->propertyAccessor->getValue($result, '[product][items]'));
    }

    public function testMergeFieldViewDataIntoItemsIsScopedByEnvelopeNotByFieldNameAlone(): void
    {
        $normalizer = new ContentViewDataNormalizer($this->propertyAccessor, self::CORE_PATHS + ['exampleRoot' => ['exampleRoot', 'content']]);

        $data = [
            'resource' => new Example(),
            'content' => [],
            'view' => [
                // a template property happens to share the field name "related" with the envelope below
                'related' => ['untouched' => 'template-value'],
            ],
            'extension' => [],
            'exampleRoot' => [
                'content' => [],
                'view' => [
                    'related' => [
                        'items' => [['id' => 1], ['id' => 2]],
                        0 => ['label' => 'first'],
                    ],
                ],
            ],
        ];

        $viewEnhancements = [
            '[exampleRoot][content][related]' => [
                'path' => ['exampleRoot', 'related'],
                'itemsPropertyName' => 'items',
                'items' => [['id' => 1], ['id' => 2]],
            ],
        ];

        $result = $normalizer->mergeFieldViewDataIntoItems($data, $viewEnhancements);

        // folded into the envelope's own [view], keyed by its type, not by the field name alone
        self::assertSame(
            [['id' => 1, 'label' => 'first'], ['id' => 2]],
            $this->propertyAccessor->getValue($result, '[exampleRoot][view][related][items]')
        );

        // the template property with the same field name keeps its own, unrelated view
        self::assertSame(
            ['untouched' => 'template-value'],
            $this->propertyAccessor->getValue($result, '[view][related]')
        );
    }
}
