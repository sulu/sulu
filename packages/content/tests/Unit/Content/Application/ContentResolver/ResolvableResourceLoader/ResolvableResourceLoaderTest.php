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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\ResolvableResourceLoader;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\ResolvableResourceLoader\ResolvableResourceLoader;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ContentResolver\Value\SmartResolvable;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Sulu\Content\Application\SmartResolver\Resolver\SmartResolverInterface;
use Sulu\Content\Application\SmartResolver\SmartResolverProviderInterface;

class ResolvableResourceLoaderTest extends TestCase
{
    private ResolvableResourceLoader $resourceLoader;
    private ResourceLoaderProvider $resourceLoaderProvider;
    private SmartResolverProviderInterface $smartResolverProvider;

    protected function setUp(): void
    {
        $exampleResourceLoader = new class() implements ResourceLoaderInterface {
            public function load(array $ids, ?string $locale, array $params = []): array
            {
                $result = [];
                foreach ($ids as $id) {
                    $result[$id] = ['title' => 'Example Resource ' . $id, 'locale' => $locale];
                }

                return $result;
            }

            public static function getKey(): string
            {
                return 'example';
            }
        };

        $smartExampleResolver = new class() implements SmartResolverInterface {
            public function resolve(SmartResolvable $resolvable, ?string $locale = null): ContentView
            {
                return ContentView::create([
                    'title' => 'Smart Example Resource',
                    'locale' => $locale,
                ], []);
            }

            public static function getType(): string
            {
                return 'smart_example';
            }
        };

        $this->resourceLoaderProvider = new ResourceLoaderProvider([
            'example' => $exampleResourceLoader,
        ]);

        $this->smartResolverProvider = new class($smartExampleResolver) implements SmartResolverProviderInterface {
            public function __construct(private SmartResolverInterface $smartResolver)
            {
            }

            public function getSmartResolver(string $type): SmartResolverInterface
            {
                if ('smart_example' === $type) {
                    return $this->smartResolver;
                }

                throw new \InvalidArgumentException(\sprintf('Smart resolver for type "%s" not found.', $type));
            }

            public function hasSmartResolver(string $type): bool
            {
                return 'smart_example' === $type;
            }
        };

        $this->resourceLoader = new ResolvableResourceLoader(
            $this->resourceLoaderProvider,
            $this->smartResolverProvider
        );
    }

    public function testLoadResourcesWithResolvableResource(): void
    {
        $resolvableResource = new ResolvableResource('123', 'example', 1);

        $resourcesPerLoader = [
            'example' => [
                '123' => [$resolvableResource->getMetadataIdentifier() => $resolvableResource],
            ],
        ];

        $result = $this->resourceLoader->loadResources($resourcesPerLoader, 'en');

        $metadataId = $resolvableResource->getMetadataIdentifier();
        self::assertArrayHasKey('example', $result);
        self::assertArrayHasKey('123', $result['example']);
        self::assertArrayHasKey($metadataId, $result['example']['123']);
        self::assertSame([
            'title' => 'Example Resource 123',
            'locale' => 'en',
        ], $result['example']['123'][$metadataId]);
    }

    public function testLoadResourcesWithSmartResolvable(): void
    {
        $smartResolvable = new SmartResolvable(
            ['id' => '456', 'title' => 'Smart Example Data'],
            'smart_example',
            10
        );

        $resourcesPerLoader = [
            'smart_example' => [
                '456' => ['default' => $smartResolvable],
            ],
        ];

        $result = $this->resourceLoader->loadResources($resourcesPerLoader, 'en');

        self::assertArrayHasKey('smart_example', $result);
        self::assertArrayHasKey('456', $result['smart_example']);
        self::assertArrayHasKey('default', $result['smart_example']['456']);
        self::assertInstanceOf(ContentView::class, $result['smart_example']['456']['default']);

        $contentView = $result['smart_example']['456']['default'];
        self::assertSame([
            'title' => 'Smart Example Resource',
            'locale' => 'en',
        ], $contentView->getContent());
    }

    public function testLoadResourcesWithMixedTypes(): void
    {
        $resolvableResource = new ResolvableResource('123', 'example', 1, fn ($resource) => $resource);
        $smartResolvable = new SmartResolvable(
            ['id' => '456', 'title' => 'Smart Example Data'],
            'smart_example',
            10
        );

        $resourcesPerLoader = [
            'example' => [
                '123' => [$resolvableResource->getMetadataIdentifier() => $resolvableResource],
            ],
            'smart_example' => [
                '456' => ['default' => $smartResolvable],
            ],
        ];

        $result = $this->resourceLoader->loadResources($resourcesPerLoader, 'en');

        self::assertArrayHasKey('example', $result);
        self::assertArrayHasKey('smart_example', $result);

        $metadataId = $resolvableResource->getMetadataIdentifier();
        self::assertSame([
            'title' => 'Example Resource 123',
            'locale' => 'en',
        ], $result['example']['123'][$metadataId]);

        self::assertInstanceOf(ContentView::class, $result['smart_example']['456']['default']);
        $contentView = $result['smart_example']['456']['default'];
        self::assertSame([
            'title' => 'Smart Example Resource',
            'locale' => 'en',
        ], $contentView->getContent());
    }

    public function testLoadResourcesWithMultipleMetadataIdentifiers(): void
    {
        $resolvableResource1 = new ResolvableResource('123', 'example', 1, fn ($resource) => $resource);
        $resolvableResource2 = new ResolvableResource('123', 'example', 1, fn ($resource) => $resource);

        $metadata1 = $resolvableResource1->getMetadataIdentifier();
        $metadata2 = $resolvableResource2->getMetadataIdentifier();

        $resourcesPerLoader = [
            'example' => [
                '123' => [
                    $metadata1 => $resolvableResource1,
                    $metadata2 => $resolvableResource2,
                ],
            ],
        ];

        $result = $this->resourceLoader->loadResources($resourcesPerLoader, 'en');

        self::assertArrayHasKey('example', $result);
        self::assertArrayHasKey('123', $result['example']);
        self::assertArrayHasKey($metadata1, $result['example']['123']);
        self::assertArrayHasKey($metadata2, $result['example']['123']);
        self::assertSame([
            'title' => 'Example Resource 123',
            'locale' => 'en',
        ], $result['example']['123'][$metadata1]);
        self::assertSame([
            'title' => 'Example Resource 123',
            'locale' => 'en',
        ], $result['example']['123'][$metadata2]);
    }

    public function testLoadResourcesLoadsEachMetadataWithItsOwnParams(): void
    {
        $recordingLoader = $this->createRecordingLoader();

        $loader = new ResolvableResourceLoader(
            new ResourceLoaderProvider(['recording' => $recordingLoader]),
            $this->smartResolverProvider,
        );

        $titleMetadata = ['properties' => ['title' => 'product.title']];
        $attributesMetadata = ['properties' => ['attributes' => 'product.attributes']];
        $title1 = new ResolvableResource('1', 'recording', 1, metadata: $titleMetadata);
        $title3 = new ResolvableResource('3', 'recording', 1, metadata: $titleMetadata);
        $attributes1 = new ResolvableResource('1', 'recording', 1, metadata: $attributesMetadata);
        $attributes2 = new ResolvableResource('2', 'recording', 1, metadata: $attributesMetadata);

        $result = $loader->loadResources([
            'recording' => [
                '1' => [
                    $title1->getMetadataIdentifier() => $title1,
                    $attributes1->getMetadataIdentifier() => $attributes1,
                ],
                '2' => [$attributes2->getMetadataIdentifier() => $attributes2],
                '3' => [$title3->getMetadataIdentifier() => $title3],
            ],
        ], 'en');

        self::assertSame([
            ['ids' => ['1', '3'], 'params' => $titleMetadata],
            ['ids' => ['1', '2'], 'params' => $attributesMetadata],
        ], $recordingLoader->calls, 'one load per metadata, all its ids at once');
        self::assertSame(['id' => '1', 'params' => $titleMetadata], $result['recording']['1'][$title1->getMetadataIdentifier()]);
        self::assertSame(['id' => '1', 'params' => $attributesMetadata], $result['recording']['1'][$attributes1->getMetadataIdentifier()]);
        self::assertSame(['id' => '2', 'params' => $attributesMetadata], $result['recording']['2'][$attributes2->getMetadataIdentifier()]);
        self::assertSame(['id' => '3', 'params' => $titleMetadata], $result['recording']['3'][$title3->getMetadataIdentifier()]);
    }

    public function testLoadResourcesLoadsResourcesWithoutMetadataApartFromTheOnesWithMetadata(): void
    {
        $recordingLoader = $this->createRecordingLoader();
        $loader = new ResolvableResourceLoader(
            new ResourceLoaderProvider(['recording' => $recordingLoader]),
            $this->smartResolverProvider,
        );

        $metadata = ['properties' => ['title' => 'title']];
        $plain5 = new ResolvableResource(5, 'recording', 1);
        $plain6 = new ResolvableResource(6, 'recording', 1);
        $withMetadata5 = new ResolvableResource(5, 'recording', 1, metadata: $metadata);

        $result = $loader->loadResources([
            'recording' => [
                5 => [
                    $plain5->getMetadataIdentifier() => $plain5,
                    $withMetadata5->getMetadataIdentifier() => $withMetadata5,
                ],
                6 => [$plain6->getMetadataIdentifier() => $plain6],
            ],
        ], 'en');

        self::assertSame([
            ['ids' => [5, 6], 'params' => []],
            ['ids' => [5], 'params' => $metadata],
        ], $recordingLoader->calls);
        self::assertSame(['id' => 5, 'params' => []], $result['recording'][5]['']);
        self::assertSame(['id' => 5, 'params' => $metadata], $result['recording'][5][$withMetadata5->getMetadataIdentifier()]);
        self::assertSame(['id' => 6, 'params' => []], $result['recording'][6]['']);
    }

    /**
     * @return ResourceLoaderInterface&object{calls: list<array{ids: list<string|int>, params: array<mixed>}>}
     */
    private function createRecordingLoader(): ResourceLoaderInterface
    {
        return new class() implements ResourceLoaderInterface {
            /** @var list<array{ids: list<string|int>, params: array<mixed>}> */
            public array $calls = [];

            public function load(array $ids, ?string $locale, array $params = []): array
            {
                $this->calls[] = ['ids' => \array_values($ids), 'params' => $params];

                $result = [];
                foreach ($ids as $id) {
                    $result[$id] = ['id' => $id, 'params' => $params];
                }

                return $result;
            }

            public static function getKey(): string
            {
                return 'recording';
            }
        };
    }

    public function testLoadResourcesWithContext(): void
    {
        $contextAwareLoader = new class() implements ResourceLoaderInterface {
            /** @var array<mixed> */
            public array $lastParams = [];

            public function load(array $ids, ?string $locale, array $params = []): array
            {
                $this->lastParams = $params;

                return ['1' => ['title' => 'test']];
            }

            public static function getKey(): string
            {
                return 'context_test';
            }
        };

        $provider = new ResourceLoaderProvider(['context_test' => $contextAwareLoader]);
        $loader = new ResolvableResourceLoader($provider, $this->smartResolverProvider);

        $resolvable = new ResolvableResource('1', 'context_test', 1);
        $resourcesPerLoader = [
            'context_test' => [
                '1' => [$resolvable->getMetadataIdentifier() => $resolvable],
            ],
        ];

        $loader->loadResources($resourcesPerLoader, 'de_li', ['_shadowLocale' => 'de']);

        self::assertSame('de', $contextAwareLoader->lastParams['_shadowLocale']);
    }

    public function testLoadResourcesWithInvalidLoaderKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ResourceLoader key "" is invalid');

        $resolvableResource = new ResolvableResource('123', '', 1, fn ($resource) => $resource);

        $resourcesPerLoader = [
            '' => [
                '123' => ['default' => $resolvableResource],
            ],
        ];

        $this->resourceLoader->loadResources($resourcesPerLoader, 'en');
    }

    public function testLoadResourcesWithMissingResourceLoader(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ResourceLoader with key "nonexistent" not found');

        $resolvableResource = new ResolvableResource('123', 'nonexistent', 1, fn ($resource) => $resource);

        $resourcesPerLoader = [
            'nonexistent' => [
                '123' => ['default' => $resolvableResource],
            ],
        ];

        $this->resourceLoader->loadResources($resourcesPerLoader, 'en');
    }

    public function testLoadResourcesWithInvalidResourceType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resource with id "123" is neither a SmartResolvable nor a ResolvableResource');

        $invalidResource = new \stdClass();

        // @phpstan-ignore-next-line
        $resourcesPerLoader = [
            'example' => [
                '123' => [
                    'default' => $invalidResource,
                ],
            ],
        ];

        $this->resourceLoader->loadResources($resourcesPerLoader, 'en'); // @phpstan-ignore-line
    }
}
