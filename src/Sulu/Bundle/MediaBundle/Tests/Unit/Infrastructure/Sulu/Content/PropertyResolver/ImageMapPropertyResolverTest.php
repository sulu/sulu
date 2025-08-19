<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\ImageMapPropertyResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Symfony\Component\ErrorHandler\BufferingLogger;

#[CoversClass(ImageMapPropertyResolver::class)]
class ImageMapPropertyResolverTest extends TestCase
{
    private ImageMapPropertyResolver $resolver;

    private BufferingLogger $logger;

    private MetadataProviderRegistry $metadataProviderRegistry;

    public function setUp(): void
    {
        $this->metadataProviderRegistry = new MetadataProviderRegistry();
        $this->metadataProviderRegistry->addMetadataProvider('form', new class() implements MetadataProviderInterface {
            public function getMetadata(string $key, string $locale, array $metadataOptions): TypedFormMetadata
            {
                return new TypedFormMetadata();
            }
        });

        $this->logger = new BufferingLogger();
        $this->resolver = new ImageMapPropertyResolver(
            $this->logger,
            $this->metadataProviderRegistry,
            debug: false,
        );
        $metadataResolverProperty = new PropertyResolverProvider([
            'default' => new DefaultPropertyResolver(),
        ]);
        $metadataResolver = new MetadataResolver($metadataResolverProperty);
        $this->resolver->setMetadataResolver($metadataResolver);
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', [], $this->createMetadata());

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertNull($content['image'] ?? null);
        $this->assertArrayHasKey('hotspots', $content);
        $this->assertInstanceOf(ContentView::class, $content['hotspots']);
        $this->assertSame([], $content['hotspots']->getContent());
        $this->assertSame([], $content['hotspots']->getView());

        $this->assertSame(['imageId' => null], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', ['custom' => 'params'], $this->createMetadata());

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertNull($content['image'] ?? null);
        $this->assertArrayHasKey('hotspots', $content);
        $this->assertInstanceOf(ContentView::class, $content['hotspots']);
        $this->assertSame([], $content['hotspots']->getContent());
        $this->assertSame([], $content['hotspots']->getView());

        $this->assertSame([
            'imageId' => null,
            'custom' => 'params',
        ], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en', [], $this->createMetadata());

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertNull($content['image'] ?? null);
        $this->assertArrayHasKey('hotspots', $content);
        $this->assertInstanceOf(ContentView::class, $content['hotspots']);
        $this->assertSame([], $content['hotspots']->getContent());
        $this->assertSame([], $content['hotspots']->getView());

        $this->assertSame(['imageId' => null], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    /**
     * @return iterable<array{
     *     0: mixed,
     * }>
     */
    public static function provideUnresolvableData(): iterable
    {
        yield 'null' => [null];
        yield 'smart_content' => [['source' => '123']];
        yield 'single_value' => [1];
        yield 'object' => [(object) [1, 2]];
        yield 'int_list_not_in_ids' => [[1, 2]];
        yield 'ids_null' => [['ids' => null]];
        yield 'ids_list' => [['ids' => [1, 2]]];
        yield 'id_list' => [['id' => [1, 2]]];
        yield 'non_numeric_image_id' => [['imageId' => 'a']];
    }

    /**
     * @param array{
     *     imageId?: string|int,
     *     hotspots?: array<array{
     *         type: string,
     *         hotspot: array{type: string},
     *         title?: string,
     *     }>,
     * } $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en', [], $this->createMetadata());

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $imageId = $data['imageId'] ?? null;
        if (null !== $imageId) {
            $imageId = (int) $imageId;
            $image = $content['image'] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $image);
            $this->assertSame($imageId, $image->getId());
            $this->assertSame('media', $image->getResourceLoaderKey());
        }

        $hotspotsContentView = $content['hotspots'] ?? null;
        $this->assertInstanceOf(ContentView::class, $hotspotsContentView);
        $hotspots = $hotspotsContentView->getContent();
        $this->assertIsArray($hotspots);

        $expectedHotspotsCount = \count($data['hotspots'] ?? []);
        $this->assertCount($expectedHotspotsCount, $hotspots);

        foreach (($data['hotspots'] ?? []) as $key => $hotspotInput) {
            $hotspotView = $hotspots[$key] ?? null;
            $this->assertInstanceOf(ContentView::class, $hotspotView);
            $hotspotContent = $hotspotView->getContent();
            $this->assertIsArray($hotspotContent);
            $this->assertSame($hotspotInput['type'], $hotspotContent['type'] ?? null);
            $this->assertSame($hotspotInput['hotspot'], $hotspotContent['hotspot'] ?? null);

            if (isset($hotspotInput['title'])) {
                $this->assertInstanceOf(ContentView::class, $hotspotContent['title'] ?? null);
                $this->assertSame($hotspotInput['title'], $hotspotContent['title']->getContent());
                $this->assertSame([], $hotspotContent['title']->getView());
            }
        }

        $this->assertSame([
            'imageId' => $imageId,
        ], $contentView->getView());

        $this->assertCount(0, $this->logger->cleanLogs());
    }

    /**
     * @return iterable<array{
     *     0: array{
     *         imageId?: string|int,
     *         hotspots?: array<array{
     *             type?: string,
     *             hotspot?: array{type: string},
     *             title?: string,
     *         }>,
     *     },
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[]];
        yield 'int_id' => [['imageId' => 1]];
        yield 'int_id_with_hotspots' => [
            ['imageId' => 1, 'hotspots' => [
                ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 1'],
                ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 2'],
            ]],
        ];
        yield 'string_id' => [['imageId' => '1']];
        yield 'string_id_with_hotspots' => [[
            'imageId' => '1',
            'hotspots' => [
                ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 1'],
                ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 2'],
            ],
        ]];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(
            ['imageId' => 1, 'hotspots' => [
                ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title'],
                ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title'],
            ]],
            'en',
            [
                'resourceLoader' => 'custom_media',
            ],
            $this->createMetadata(),
        );

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $image = $content['image'] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $image);
        $this->assertSame(1, $image->getId());
        $this->assertSame('custom_media', $image->getResourceLoaderKey());

        $this->assertInstanceOf(ContentView::class, $content['hotspots'] ?? null);
        $hotspotsContentView = $content['hotspots'];
        $this->assertSame([], $hotspotsContentView->getView());

        $hotspots = $hotspotsContentView->getContent();
        $this->assertIsArray($hotspots);
        $this->assertCount(2, $hotspots);

        foreach ($hotspots as $hotspotView) {
            $this->assertInstanceOf(ContentView::class, $hotspotView);
            $hotspotContent = $hotspotView->getContent();
            $this->assertIsArray($hotspotContent);
            $this->assertSame('text', $hotspotContent['type'] ?? null);
            $this->assertSame(['type' => 'circle'], $hotspotContent['hotspot'] ?? null);
            $this->assertInstanceOf(ContentView::class, $hotspotContent['title'] ?? null);
            $this->assertSame('Title', $hotspotContent['title']->getContent());
            $this->assertSame([], $hotspotContent['title']->getView());
        }

        $this->assertSame([
            'imageId' => 1,
            'resourceLoader' => 'custom_media',
        ], $contentView->getView());
        $this->assertCount(0, $this->logger->cleanLogs());
    }

    /**
     * @param array{
     *     imageId: int,
     *     hotspots?: array<array{
     *         type?: string,
     *         hotspot?: array{type: string},
     *         ...
     *     }>,
     * } $data
     */
    #[DataProvider('provideUnresolvableHotspotData')]
    public function testResolveUnresolvableHotspotData(array $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en', [], $this->createMetadata());

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $image = $content['image'] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $image);
        $this->assertSame(1, $image->getId());
        $this->assertSame('media', $image->getResourceLoaderKey());

        $hotspotsContentView = $content['hotspots'] ?? null;
        $this->assertInstanceOf(ContentView::class, $hotspotsContentView);
        $hotspots = $hotspotsContentView->getContent();
        $this->assertIsArray($hotspots);

        $expectedCount = \count($data['hotspots'] ?? []);
        $expectedErrorLogs = 0;
        foreach ($data['hotspots'] ?? [] as $hotspot) {
            if (!isset($hotspot['type'])) {
                --$expectedCount;
                continue;
            }
            if (!isset($hotspot['hotspot'])) {
                --$expectedCount;
                continue;
            }
            ++$expectedErrorLogs;
        }

        $this->assertCount($expectedCount, $hotspots);

        $this->assertSame(['imageId' => 1], $contentView->getView());
        $logs = $this->logger->cleanLogs();
        $this->assertCount($expectedErrorLogs, $logs);
    }

    /**
     * @return iterable<array{
     *     0: mixed,
     * }>
     */
    public static function provideUnresolvableHotspotData(): iterable
    {
        yield 'hotspot_with_not_exist_type' => [['imageId' => 1, 'hotspots' => [['type' => 'not_exist', 'hotspot' => ['type' => 'circle'], 'title' => 'Title']]]];
        yield 'hotspot_with_no_type' => [['imageId' => 1, 'hotspots' => [['hotspot' => ['type' => 'circle'], 'title' => 'Title']]]];
        yield 'hotspot_with_no_hotspot' => [['imageId' => 1, 'hotspots' => [['type' => 'not_exist', 'title' => 'Title']]]];
    }

    public function testResolveGlobalBlockHotspot(): void
    {
        $data = ['imageId' => 1, 'hotspots' => [
            ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 1'],
            ['type' => 'text', 'hotspot' => ['type' => 'circle'], 'title' => 'Title 2'],
        ]];

        $textFormMetadata = new FormMetadata();
        $textFormMetadata->setKey('text');
        $itemMetadata = new FieldMetadata('title');
        $itemMetadata->setType('text_line');
        $textFormMetadata->addItem($itemMetadata);
        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm('text', $textFormMetadata);

        $this->metadataProviderRegistry->addMetadataProvider('form', new class($typedFormMetadata) implements MetadataProviderInterface {
            public function __construct(private readonly TypedFormMetadata $typedFormMetadata)
            {
            }

            public function getMetadata(string $key, string $locale, array $metadataOptions): TypedFormMetadata
            {
                return $this->typedFormMetadata;
            }
        });

        $contentView = $this->resolver->resolve($data, 'en', [], $this->createGlobalBlockMetadata());

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $imageId = $data['imageId'];
        $this->assertSame(['imageId' => $imageId], $contentView->getView());

        $hotspotsContentView = $content['hotspots'] ?? null;
        $this->assertInstanceOf(ContentView::class, $hotspotsContentView);
        $hotspots = $hotspotsContentView->getContent();
        $this->assertIsArray($hotspots);

        foreach (($data['hotspots']) as $key => $hotspotInput) {
            $hotspotView = $hotspots[$key] ?? null;
            $this->assertInstanceOf(ContentView::class, $hotspotView);
            $hotspotContent = $hotspotView->getContent();
            $this->assertIsArray($hotspotContent);
            $this->assertSame($hotspotInput['type'], $hotspotContent['type'] ?? null);
            $this->assertSame($hotspotInput['hotspot'], $hotspotContent['hotspot'] ?? null);
            $this->assertInstanceOf(ContentView::class, $hotspotContent['title'] ?? null);
            $this->assertSame($hotspotInput['title'], $hotspotContent['title']->getContent());
            $this->assertSame([], $hotspotContent['title']->getView());
        }

        $this->assertCount(0, $this->logger->cleanLogs());
    }

    private function createMetadata(): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata('image');
        $fieldMetadata->setType('image_map');
        $fieldMetadata->setDefaultType('text');

        $textFormMetadata = new FormMetadata();
        $textFormMetadata->setKey('text');
        $itemMetadata = new FieldMetadata('title');
        $itemMetadata->setType('text_line');
        $textFormMetadata->addItem($itemMetadata);

        $fieldMetadata->addType($textFormMetadata);

        return $fieldMetadata;
    }

    private function createGlobalBlockMetadata(): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata('image');
        $fieldMetadata->setType('image_map');
        $fieldMetadata->setDefaultType('text');

        $textFormMetadata = new FormMetadata();
        $textFormMetadata->setKey('text');

        $tag = new TagMetadata();
        $tag->setName('sulu.global_block');
        $tag->setAttributes(['global_block' => 'text']);
        $textFormMetadata->setTags([
            $tag,
        ]);

        $fieldMetadata->addType($textFormMetadata);

        return $fieldMetadata;
    }
}
