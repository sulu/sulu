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
        self::assertSame(5, $references[0]->getResourceId());
        self::assertSame('pages', $references[0]->getResourceKey());
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

        $references = $contentView->getReferences();
        self::assertCount(2, $references);
        self::assertSame(5, $references[0]->getResourceId());
        self::assertSame('articles', $references[0]->getResourceKey());
        self::assertSame(6, $references[1]->getResourceId());
        self::assertSame('articles', $references[1]->getResourceKey());
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
}
