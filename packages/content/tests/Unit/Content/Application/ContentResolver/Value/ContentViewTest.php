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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Value;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\Reference;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

class ContentViewTest extends TestCase
{
    public function testCreate(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);

        self::assertSame('content', $contentView->getContent());
        self::assertSame(['view' => 'data'], $contentView->getView());
    }

    public function testCreateResolvable(): void
    {
        $contentView = ContentView::createResolvable(5, 'resourceLoaderKey', ['view' => 'data']);

        $content = $contentView->getContent();
        /** @var ResolvableResource $resolvable */
        $resolvable = $content;
        self::assertSame(5, $resolvable->getId());
        self::assertSame('resourceLoaderKey', $resolvable->getResourceLoaderKey());
        self::assertSame(['view' => 'data'], $contentView->getView());
    }

    public function testCreateResolvables(): void
    {
        $contentView = ContentView::createResolvables([5, 6], 'resourceLoaderKey', ['view' => 'data']);

        /** @var ResolvableResource[] $resolvables */
        $resolvables = $contentView->getContent();
        self::assertCount(2, $resolvables);
        self::assertSame(5, $resolvables[0]->getId());
        self::assertSame('resourceLoaderKey', $resolvables[0]->getResourceLoaderKey());
        self::assertSame(6, $resolvables[1]->getId());
        self::assertSame('resourceLoaderKey', $resolvables[1]->getResourceLoaderKey());
        self::assertSame(['view' => 'data'], $contentView->getView());
    }

    public function testGetContent(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);

        self::assertSame('content', $contentView->getContent());
    }

    public function testGetView(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);

        self::assertSame(['view' => 'data'], $contentView->getView());
    }

    public function testCreateWithReferences(): void
    {
        $references = [
            new Reference(1, 'pages'),
            new Reference('uuid', 'articles'),
        ];
        $contentView = ContentView::createWithReferences('content', ['view' => 'data'], $references);

        self::assertSame('content', $contentView->getContent());
        self::assertSame(['view' => 'data'], $contentView->getView());
        self::assertSame($references, $contentView->getReferences());
    }

    public function testCreateResolvableWithReferences(): void
    {
        $contentView = ContentView::createResolvableWithReferences(
            5,
            'resourceLoaderKey',
            'pages',
            ['view' => 'data']
        );

        $content = $contentView->getContent();
        /** @var ResolvableResource $resolvable */
        $resolvable = $content;
        self::assertSame(5, $resolvable->getId());
        self::assertSame('resourceLoaderKey', $resolvable->getResourceLoaderKey());
        self::assertSame(['view' => 'data'], $contentView->getView());

        $references = $contentView->getReferences();
        self::assertCount(1, $references);
        $this->assertReferenceEquals(5, 'pages', '', $references[0]);
    }

    public function testCreateResolvablesWithReferences(): void
    {
        $contentView = ContentView::createResolvablesWithReferences(
            [5, 6],
            'resourceLoaderKey',
            'articles',
            ['view' => 'data']
        );

        /** @var ResolvableResource[] $resolvables */
        $resolvables = $contentView->getContent();
        self::assertCount(2, $resolvables);
        self::assertSame(5, $resolvables[0]->getId());
        self::assertSame('resourceLoaderKey', $resolvables[0]->getResourceLoaderKey());
        self::assertSame(6, $resolvables[1]->getId());
        self::assertSame('resourceLoaderKey', $resolvables[1]->getResourceLoaderKey());
        self::assertSame(['view' => 'data'], $contentView->getView());

        $expectedReferences = [
            ['id' => 5, 'key' => 'articles', 'path' => ''],
            ['id' => 6, 'key' => 'articles', 'path' => ''],
        ];
        $this->assertReferencesMatch($expectedReferences, $contentView->getReferences());
    }

    public function testGetReferences(): void
    {
        $references = [new Reference(1, 'pages')];
        $contentView = ContentView::createWithReferences('content', ['view' => 'data'], $references);

        self::assertSame($references, $contentView->getReferences());
    }

    public function testSetReferences(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);
        self::assertEmpty($contentView->getReferences());

        $references = [new Reference(1, 'pages'), new Reference(2, 'articles')];
        $result = $contentView->setReferences($references);

        self::assertSame($contentView, $result); // Test fluent interface
        self::assertSame($references, $contentView->getReferences());
    }

    public function testBackwardCompatibilityWithoutReferences(): void
    {
        $contentView = ContentView::create('content', ['view' => 'data']);

        self::assertSame('content', $contentView->getContent());
        self::assertSame(['view' => 'data'], $contentView->getView());
        self::assertEmpty($contentView->getReferences());
    }

    /**
     * @param array<Reference> $inputReferences
     * @param array<array{id: string|int, key: string, path: string}> $expectedReferences
     */
    #[DataProvider('simpleReferenceTestDataProvider')]
    public function testGetAllReferencesRecursivelySimpleCases(
        array $inputReferences,
        string $basePath,
        array $expectedReferences,
        mixed $content = 'content'
    ): void {
        $contentView = ContentView::createWithReferences($content, ['view' => 'data'], $inputReferences);

        $allReferences = \iterator_to_array($contentView->getAllReferencesRecursively($basePath));

        $this->assertReferencesMatch($expectedReferences, $allReferences);
    }

    public function testGetAllReferencesRecursivelyWithNestedContentViews(): void
    {
        $nestedRef1 = new Reference(10, 'snippets');
        $nestedRef2 = new Reference(20, 'media');

        $nestedContentView1 = ContentView::createWithReferences('nested1', [], [$nestedRef1]);
        $nestedContentView2 = ContentView::createWithReferences('nested2', [], [$nestedRef2]);

        $mainRef = new Reference(1, 'pages');
        $mainContentView = ContentView::createWithReferences(
            ['child1' => $nestedContentView1, 'child2' => $nestedContentView2],
            [],
            [$mainRef]
        );

        $allReferences = \iterator_to_array($mainContentView->getAllReferencesRecursively('content'));

        $expectedReferences = [
            ['id' => 1, 'key' => 'pages', 'path' => 'content'],
            ['id' => 10, 'key' => 'snippets', 'path' => 'content.child1'],
            ['id' => 20, 'key' => 'media', 'path' => 'content.child2'],
        ];

        $this->assertReferencesMatch($expectedReferences, $allReferences);
    }

    public function testGetAllReferencesRecursivelyWithDeeplyNestedContentViews(): void
    {
        $deepNestedRef = new Reference(100, 'contacts');
        $deepNestedContentView = ContentView::createWithReferences('deep', [], [$deepNestedRef]);

        $middleContentView = ContentView::createWithReferences(['level3' => $deepNestedContentView], [], []);
        $topContentView = ContentView::createWithReferences(['level2' => $middleContentView], [], []);

        $allReferences = \iterator_to_array($topContentView->getAllReferencesRecursively('content'));

        $this->assertReferenceEquals(100, 'contacts', 'content.level2.level3', $allReferences[0]);
        self::assertCount(1, $allReferences);
    }

    public function testGetAllReferencesRecursivelyWithMixedContent(): void
    {
        $nestedRef = new Reference(50, 'categories');
        $nestedContentView = ContentView::createWithReferences('nested', [], [$nestedRef]);

        $mixedContent = [
            'text' => 'some text',
            0 => $nestedContentView,
            'number' => 123,
            'nested' => $nestedContentView,
        ];

        $mainRef = new Reference(1, 'pages');
        $mainContentView = ContentView::createWithReferences($mixedContent, [], [$mainRef]);

        $allReferences = \iterator_to_array($mainContentView->getAllReferencesRecursively());

        $expectedReferences = [
            ['id' => 1, 'key' => 'pages', 'path' => ''],
            ['id' => 50, 'key' => 'categories', 'path' => '0'],
            ['id' => 50, 'key' => 'categories', 'path' => 'nested'],
        ];

        $this->assertReferencesMatch($expectedReferences, $allReferences);
    }

    public function testGetAllReferencesRecursivelyWithEmptyContent(): void
    {
        $contentView = ContentView::create('simple content', []);

        $allReferences = \iterator_to_array($contentView->getAllReferencesRecursively());

        self::assertEmpty($allReferences);
    }

    /**
     * Data provider for simple reference test cases.
     *
     * @return array<string, array{0: array<Reference>, 1: string, 2: array<array{id: string|int, key: string, path: string}>, 3?: mixed}>
     */
    public static function simpleReferenceTestDataProvider(): array
    {
        return [
            'single reference without base path' => [
                [new Reference(1, 'pages')],
                '',
                [['id' => 1, 'key' => 'pages', 'path' => '']],
            ],
            'multiple references without base path' => [
                [
                    new Reference(1, 'pages'),
                    new Reference('uuid-123', 'articles'),
                ],
                '',
                [
                    ['id' => 1, 'key' => 'pages', 'path' => ''],
                    ['id' => 'uuid-123', 'key' => 'articles', 'path' => ''],
                ],
            ],
            'single reference with base path' => [
                [new Reference(1, 'pages')],
                'seo.title',
                [['id' => 1, 'key' => 'pages', 'path' => 'seo.title']],
            ],
            'single reference with non-iterable content and path' => [
                [new Reference(1, 'pages')],
                'test',
                [['id' => 1, 'key' => 'pages', 'path' => 'test']],
                'string content',
            ],
        ];
    }

    private function assertReferenceEquals(string|int $expectedId, string $expectedKey, string $expectedPath, Reference $actual): void
    {
        self::assertSame($expectedId, $actual->getResourceId());
        self::assertSame($expectedKey, $actual->getResourceKey());
        self::assertSame($expectedPath, $actual->getPath());
    }

    /**
     * @param array<array{id: string|int, key: string, path: string}> $expectedReferences
     * @param array<Reference> $actualReferences
     */
    private function assertReferencesMatch(array $expectedReferences, array $actualReferences): void
    {
        self::assertCount(\count($expectedReferences), $actualReferences);

        foreach ($expectedReferences as $index => $expected) {
            $this->assertReferenceEquals(
                $expected['id'],
                $expected['key'],
                $expected['path'],
                $actualReferences[$index]
            );
        }
    }
}
