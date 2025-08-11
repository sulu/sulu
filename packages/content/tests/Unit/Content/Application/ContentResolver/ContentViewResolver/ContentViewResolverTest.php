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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\ContentViewResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolver;
use Sulu\Content\Application\ContentResolver\ResolvableResourceQueue\ResolvableResourceQueueProcessor;
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentViewResolverTest extends TestCase
{
    use ProphecyTrait;

    private ContentViewResolver $contentViewResolver;
    private ResolvableResourceQueueProcessor $resolvableResourceQueueProcessor;

    /**
     * @var ObjectProphecy<ResolverInterface>[]
     */
    private array $contentResolvers = [];

    protected function setUp(): void
    {
        $this->resolvableResourceQueueProcessor = new ResolvableResourceQueueProcessor();

        $templateResolver = $this->prophesize(ResolverInterface::class);
        $settingsResolver = $this->prophesize(ResolverInterface::class);

        $this->contentResolvers = [
            'template' => $templateResolver,
            'settings' => $settingsResolver,
        ];

        $this->contentViewResolver = new ContentViewResolver(
            $this->resolvableResourceQueueProcessor,
            [
                'template' => $templateResolver->reveal(),
                'settings' => $settingsResolver->reveal(),
            ]
        );
    }

    public function testGetContentViews(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $templateContentView = ContentView::create(['title' => 'Test Title'], ['title' => 'Title Field']);
        $settingsContentView = ContentView::create(['seo' => 'test'], ['seo' => 'SEO Field']);

        $this->contentResolvers['template']->resolve($dimensionContent, null)
            ->shouldBeCalled()
            ->willReturn($templateContentView);

        $this->contentResolvers['settings']->resolve($dimensionContent, null)
            ->willReturn($settingsContentView)
            ->shouldBeCalledOnce();

        $result = $this->contentViewResolver->getContentViews($dimensionContent);

        self::assertCount(2, $result);
        self::assertSame($templateContentView, $result['template']);
        self::assertSame($settingsContentView, $result['settings']);
    }

    public function testGetContentViewsWithNullResolver(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setLocale('en');

        $templateContentView = ContentView::create(['title' => 'Test Title'], ['title' => 'Title Field']);

        $this->contentResolvers['template']->resolve($dimensionContent, null)
            ->willReturn($templateContentView)
            ->shouldBeCalledOnce();

        $this->contentResolvers['settings']->resolve($dimensionContent, null)
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $result = $this->contentViewResolver->getContentViews($dimensionContent);

        self::assertCount(1, $result);
        self::assertSame($templateContentView, $result['template']);
        self::assertArrayNotHasKey('settings', $result);
    }

    public function testResolveContentViews(): void
    {
        $contentView1 = ContentView::create(['title' => 'Test'], ['title' => 'Title']);
        $contentView2 = ContentView::create(['description' => 'Desc'], ['description' => 'Description']);

        $contentViews = [
            'template' => $contentView1,
            'settings' => $contentView2,
        ];

        $priorityQueue = [];
        $result = $this->contentViewResolver->resolveContentViews($contentViews, 0, $priorityQueue);

        // Check the structure of the result
        self::assertArrayHasKey('template', $result['content']);
        self::assertArrayHasKey('settings', $result['content']);
        self::assertSame(['title' => 'Test'], $result['content']['template']);
        self::assertSame(['description' => 'Desc'], $result['content']['settings']);
        self::assertSame([], $result['resolvableResources']);
    }

    public function testResolveContentViewWithResolvableResource(): void
    {
        $resolvableResource = new ResolvableResource('123', 'page', 1);

        $contentView = ContentView::create(['page' => $resolvableResource], ['page' => 'Page']);

        $priorityQueue = [];
        $result = $this->contentViewResolver->resolveContentView($contentView, 'test', 0, $priorityQueue);

        self::assertSame(['page' => $resolvableResource], $result['content']['test']);
        self::assertSame(['page' => 'Page'], $result['view']['test']);

        // Verify that the resolvable resource was correctly added to the result
        $expectedPriority = $resolvableResource->getPriority();
        $expectedLoaderKey = $resolvableResource->getResourceLoaderKey();
        $expectedId = $resolvableResource->getId();
        $expectedMetadataId = $resolvableResource->getMetadataIdentifier();

        self::assertArrayHasKey($expectedPriority, $result['resolvableResources']);
        self::assertArrayHasKey($expectedLoaderKey, $result['resolvableResources'][$expectedPriority]);
        self::assertArrayHasKey(0, $result['resolvableResources'][$expectedPriority][$expectedLoaderKey]);
        self::assertArrayHasKey($expectedId, $result['resolvableResources'][$expectedPriority][$expectedLoaderKey][0]);
        self::assertArrayHasKey($expectedMetadataId, $result['resolvableResources'][$expectedPriority][$expectedLoaderKey][0][$expectedId]);
        self::assertSame($resolvableResource, $result['resolvableResources'][$expectedPriority][$expectedLoaderKey][0][$expectedId][$expectedMetadataId]);
    }

    public function testResolveContentViewWithNestedContentViews(): void
    {
        $nestedContentView = ContentView::create(['nested' => 'data'], ['nested' => 'view']);
        $contentView = ContentView::create([$nestedContentView], []);

        $priorityQueue = [];
        $result = $this->contentViewResolver->resolveContentView($contentView, 'test', 0, $priorityQueue);

        // Verify basic structure
        self::assertArrayHasKey('test', $result['content']);
        self::assertArrayHasKey('test', $result['view']);

        // Type-safe access to nested arrays
        /** @var array<int, array<string, mixed>> $testContent */
        $testContent = $result['content']['test'];
        self::assertArrayHasKey(0, $testContent);

        /** @var array<string, mixed> $firstItem */
        $firstItem = $testContent[0];
        self::assertArrayHasKey('nested', $firstItem);
        self::assertSame('data', $firstItem['nested']);

        /** @var array<int, array<string, mixed>> $testView */
        $testView = $result['view']['test'];
        self::assertArrayHasKey(0, $testView);

        /** @var array<string, mixed> $firstViewItem */
        $firstViewItem = $testView[0];
        self::assertArrayHasKey('nested', $firstViewItem);
        self::assertSame('view', $firstViewItem['nested']);
    }

    public function testResolvableResourceQueueProcessorIntegration(): void
    {
        $resolvableResource1 = new ResolvableResource('123', 'page', 1, fn ($resource) => $resource);
        $resolvableResource2 = new ResolvableResource('456', 'article', 2, fn ($resource) => $resource);

        $contentView1 = ContentView::create(['page' => $resolvableResource1], ['page' => 'Page Field']);
        $contentView2 = ContentView::create(['article' => $resolvableResource2], ['article' => 'Article Field']);

        $contentViews = [
            'template' => $contentView1,
            'sidebar' => $contentView2,
        ];

        $priorityQueue = [];
        $result = $this->contentViewResolver->resolveContentViews($contentViews, 0, $priorityQueue);

        self::assertNotEmpty($result['resolvableResources']);

        // Resources should be sorted by priority descending (higher priority first)
        $priorities = \array_keys($result['resolvableResources']);
        self::assertSame([2, 1], $priorities, 'Resources should be sorted by priority in descending order');

        // Verify priority 2 resource (article)
        self::assertArrayHasKey('article', $result['resolvableResources'][2]);
        self::assertSame($resolvableResource2, $result['resolvableResources'][2]['article'][0]['456'][$resolvableResource2->getMetadataIdentifier()]);

        // Verify priority 1 resource (page)
        self::assertArrayHasKey('page', $result['resolvableResources'][1]);
        self::assertSame($resolvableResource1, $result['resolvableResources'][1]['page'][0]['123'][$resolvableResource1->getMetadataIdentifier()]);
    }
}
