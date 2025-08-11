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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolver;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolver;
use Sulu\Content\Application\ContentResolver\DataNormalizer\ContentViewDataNormalizer;
use Sulu\Content\Application\ContentResolver\ResolvableResourceLoader\ResolvableResourceLoaderInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceQueue\ResolvableResourceQueueProcessor;
use Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer\ResolvableResourceReplacer;
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\ContentRichEntityTrait;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ContentResolverTest extends TestCase
{
    use ProphecyTrait;

    private ContentResolver $contentResolver;
    private ResolvableResourceQueueProcessor $resolvableResourceQueueProcessor;
    private ResolvableResourceReplacer $resolvableResourceReplacer;
    private ContentViewDataNormalizer $contentViewDataNormalizer;
    private ContentViewResolver $contentViewResolver;
    private TestTemplateResolver $templateResolver;

    /**
     * @var ObjectProphecy<ResolvableResourceLoaderInterface>
     */
    private ObjectProphecy $resolvableResourceLoader;

    /**
     * @var ObjectProphecy<ContentAggregatorInterface>
     */
    private ObjectProphecy $contentAggregator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolvableResourceQueueProcessor = new ResolvableResourceQueueProcessor();
        $this->resolvableResourceReplacer = new ResolvableResourceReplacer();
        $this->contentViewDataNormalizer = new ContentViewDataNormalizer(new PropertyAccessor());

        $this->templateResolver = new TestTemplateResolver();

        $this->contentViewResolver = new ContentViewResolver(
            $this->resolvableResourceQueueProcessor,
            ['template' => $this->templateResolver]
        );

        $this->resolvableResourceLoader = $this->prophesize(ResolvableResourceLoaderInterface::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $maxDepth = 5;

        $this->contentResolver = new ContentResolver(
            $this->contentViewResolver,
            $this->resolvableResourceLoader->reveal(),
            $this->resolvableResourceQueueProcessor,
            $this->resolvableResourceReplacer,
            $this->contentViewDataNormalizer,
            $this->contentAggregator->reveal(),
            $maxDepth
        );
    }

    public function testResolveSimpleExample(): void
    {
        $example = new TestExample();
        $example->id = 111;

        $dimensionContent = new TestExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setStage('live');
        $dimensionContent->setLocale('en');

        $templateContentView = ContentView::create(
            ['title' => 'Test Example', 'article' => '<p>Test content</p>'],
            ['title' => 'Title Field', 'article' => 'Article Field']
        );
        $this->templateResolver->setContentView($templateContentView);

        $result = $this->contentResolver->resolve($dimensionContent);

        self::assertSame($example, $result['resource']);
        self::assertSame(['title' => 'Test Example', 'article' => '<p>Test content</p>'], $result['content']);
        self::assertSame(['title' => 'Title Field', 'article' => 'Article Field'], $result['view']);
        self::assertSame([], $result['extension']);
    }

    public function testResolveExampleWithResolvableResources(): void
    {
        $example = new TestExample();
        $example->id = 222;

        $dimensionContent = new TestExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setStage('live');
        $dimensionContent->setLocale('en');

        $templateContentView = ContentView::create(
            ['title' => 'Main Example', 'description' => 'A simple example'],
            ['title' => 'Title Field', 'description' => 'Description Field']
        );
        $this->templateResolver->setContentView($templateContentView);

        $result = $this->contentResolver->resolve($dimensionContent);

        self::assertSame($example, $result['resource']);
        self::assertSame('Main Example', $result['content']['title']);
        self::assertSame('A simple example', $result['content']['description']);
        self::assertSame(['title' => 'Title Field', 'description' => 'Description Field'], $result['view']);
        self::assertSame([], $result['extension']);
    }

    public function testResolveExampleWithMultiplePriorities(): void
    {
        $example = new TestExample();
        $example->id = 333;

        $dimensionContent = new TestExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setStage('live');
        $dimensionContent->setLocale('en');

        $highPriorityResource = new ResolvableResource('333', 'example', 10, fn ($resource) => $resource);
        $lowPriorityResource = new ResolvableResource('444', 'example', 1, fn ($resource) => $resource);

        $templateContentView = ContentView::create(
            [
                'title' => 'Main Example',
                'high_priority_example' => $highPriorityResource,
                'low_priority_example' => $lowPriorityResource,
            ],
            ['title' => 'Title Field', 'high_priority_example' => 'High Priority', 'low_priority_example' => 'Low Priority']
        );
        $this->templateResolver->setContentView($templateContentView);

        $this->resolvableResourceLoader->loadResources(
            ['example' => ['333' => [$highPriorityResource->getMetadataIdentifier() => $highPriorityResource]]],
            'en'
        )->willReturn(['example' => ['333' => [$highPriorityResource->getMetadataIdentifier() => 'High Priority Result']]]);

        $this->resolvableResourceLoader->loadResources(
            ['example' => ['444' => [$lowPriorityResource->getMetadataIdentifier() => $lowPriorityResource]]],
            'en'
        )->willReturn(['example' => ['444' => [$lowPriorityResource->getMetadataIdentifier() => 'Low Priority Result']]]);

        $result = $this->contentResolver->resolve($dimensionContent);

        self::assertSame($example, $result['resource']);
        self::assertSame('Main Example', $result['content']['title']);
        self::assertArrayHasKey('high_priority_example', $result['content']);
        self::assertArrayHasKey('low_priority_example', $result['content']);
    }

    public function testResolveWithEmptyResources(): void
    {
        $example = new TestExample();
        $example->id = 444;

        $dimensionContent = new TestExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);
        $dimensionContent->setStage('live');
        $dimensionContent->setLocale('en');

        $templateContentView = ContentView::create(
            ['title' => 'Empty Example', 'content' => ''],
            ['title' => 'Title Field', 'content' => 'Content Field']
        );
        $this->templateResolver->setContentView($templateContentView);

        $result = $this->contentResolver->resolve($dimensionContent);

        self::assertSame($example, $result['resource']);
        self::assertSame(['title' => 'Empty Example', 'content' => ''], $result['content']);
        self::assertSame(['title' => 'Title Field', 'content' => 'Content Field'], $result['view']);
        self::assertSame([], $result['extension']);
    }
}

class TestTemplateResolver implements ResolverInterface
{
    private ?ContentView $contentView = null;

    public function setContentView(?ContentView $contentView): void
    {
        $this->contentView = $contentView;
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        return $this->contentView;
    }
}

/**
 * @implements ContentRichEntityInterface<TestExampleDimensionContent>
 */
class TestExample implements ContentRichEntityInterface
{
    /**
     * @phpstan-use ContentRichEntityTrait<TestExampleDimensionContent>
     */
    use ContentRichEntityTrait;

    public const RESOURCE_KEY = 'examples';
    public const TEMPLATE_TYPE = 'example';

    public int $id;

    public function getId(): int
    {
        return $this->id;
    }

    public function createDimensionContent(): DimensionContentInterface
    {
        return new TestExampleDimensionContent($this);
    }

    public static function getResourceKey(): string
    {
        return self::RESOURCE_KEY;
    }
}

/**
 * @implements DimensionContentInterface<TestExample>
 */
class TestExampleDimensionContent implements DimensionContentInterface
{
    use DimensionContentTrait;

    protected int $id;
    protected TestExample $example;
    protected ?string $title = null;

    public function __construct(TestExample $example)
    {
        $this->example = $example;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getResource(): ContentRichEntityInterface
    {
        return $this->example;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public static function getResourceKey(): string
    {
        return TestExample::RESOURCE_KEY;
    }
}
