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

namespace Sulu\Content\Tests\Unit\Content\Application\PropertyResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\PropertyResolver\Resolver\TeaserSelectionPropertyResolver;

class TeaserSelectionPropertyResolverTest extends TestCase
{
    private TeaserSelectionPropertyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TeaserSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['presentAs' => null, 'items' => []], $contentView->getView());
    }

    public function testResolveWrongData(): void
    {
        $contentView = $this->resolver->resolve(['source' => 1], 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['presentAs' => null, 'items' => []], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['presentAs' => null, 'items' => [], 'custom' => 'params'], $contentView->getView());
    }

    public function testResolveData(): void
    {
        $data = [
            'presentAs' => 'two-columns',
            'items' => [
                ['id' => '123', 'type' => 'article'],
                ['id' => '456', 'type' => 'page'],
            ],
        ];

        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(2, $content);

        $resolvable1 = $content[0];
        $this->assertInstanceOf(ResolvableResource::class, $resolvable1);
        $this->assertSame('article::123', $resolvable1->getId());
        $this->assertSame('teaser', $resolvable1->getResourceLoaderKey());

        $resolvable2 = $content[1];
        $this->assertInstanceOf(ResolvableResource::class, $resolvable2);
        $this->assertSame('page::456', $resolvable2->getId());
        $this->assertSame('teaser', $resolvable2->getResourceLoaderKey());

        $view = $contentView->getView();
        $this->assertSame('two-columns', $view['presentAs']);
        $this->assertIsArray($view['items']);
        $this->assertCount(2, $view['items']);
        $this->assertSame(['id' => '123', 'type' => 'article'], $view['items'][0]);
        $this->assertSame(['id' => '456', 'type' => 'page'], $view['items'][1]);
    }

    public function testResolveCustomResourceLoader(): void
    {
        $data = [
            'items' => [
                ['id' => '123', 'type' => 'article'],
            ],
        ];

        $contentView = $this->resolver->resolve($data, 'en', ['resourceLoader' => 'custom_teaser']);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);

        $resolvable = $content[0];
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame('article::123', $resolvable->getId());
        $this->assertSame('custom_teaser', $resolvable->getResourceLoaderKey());
    }

    public function testResolveWithResourceCallback(): void
    {
        $data = [
            'items' => [
                [
                    'id' => '123',
                    'type' => 'article',
                    'title' => 'Article Title',
                    'description' => 'Article Description',
                    'mediaId' => 11,
                ],
            ],
        ];

        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertCount(1, $content);

        $resolvable = $content[0];
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame('article::123', $resolvable->getId());
        $this->assertSame('teaser', $resolvable->getResourceLoaderKey());

        $teaser = new Teaser('123', 'article', 'en', '', '', '', 'http://example.com', 1);
        $mergedTeaser = $resolvable->executeResourceCallback($teaser);
        $this->assertInstanceOf(Teaser::class, $mergedTeaser);
        $this->assertSame('Article Title', $mergedTeaser->getTitle());
        $this->assertSame('Article Description', $mergedTeaser->getDescription());
        $this->assertSame(11, $mergedTeaser->getMediaId());
    }

    public function testGetType(): void
    {
        $this->assertSame('teaser_selection', TeaserSelectionPropertyResolver::getType());
    }
}
