<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\Page\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageSelectionPropertyResolver;

#[CoversClass(PageSelectionPropertyResolver::class)]
class PageSelectionPropertyResolverTest extends TestCase
{
    private PageSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new PageSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => []], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertSame([], $contentView->getContent());
        $this->assertSame([
            'ids' => [],
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame([], $contentView->getContent());
        $this->assertSame(['ids' => []], $contentView->getView());
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
    }

    /**
     * @param array<string|int> $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        foreach ($data as $key => $value) {
            $resolvable = $content[$key] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $resolvable);
            $this->assertSame($value, $resolvable->getId());
            $this->assertSame('page', $resolvable->getResourceLoaderKey());
        }

        $references = $contentView->getReferences();
        $this->assertCount(\count($data), $references);
        foreach ($data as $key => $value) {
            $reference = $references[$key] ?? null;
            $this->assertNotNull($reference);
            $this->assertSame($value, $reference->getResourceId());
            $this->assertSame(PageInterface::RESOURCE_KEY, $reference->getResourceKey());
        }

        $this->assertSame(['ids' => $data], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: array<string|int>,
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[]];
        yield 'string_list' => [['1', '2']];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve([1], 'en', ['resourceLoader' => 'custom_Page']);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $resolvable = $content[0] ?? null;
        $this->assertInstanceOf(ResolvableResource::class, $resolvable);
        $this->assertSame(1, $resolvable->getId());
        $this->assertSame('custom_Page', $resolvable->getResourceLoaderKey());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame(1, $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithMetadata(): void
    {
        $contentView = $this->resolver->resolve(['1'], 'en', [
            'properties' => [
                'property1' => 'value1',
                'property2' => 'value2',
            ],
        ]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $this->assertSame([
            'properties' => [
                'property1' => 'value1',
                'property2' => 'value2',
            ],
        ], $content[0]->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithoutMetadata(): void
    {
        $contentView = $this->resolver->resolve(['1'], 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $this->assertSame([
            'properties' => null,
        ], $content[0]->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }

    public function testResolveWithEmptyMetadata(): void
    {
        $metadata = new FieldMetadata('test_field');

        $contentView = $this->resolver->resolve(['1'], 'en', ['metadata' => $metadata]);

        $content = $contentView->getContent();
        $this->assertIsArray($content);
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);

        $this->assertSame([
            'properties' => null,
        ], $content[0]->getMetadata());

        $references = $contentView->getReferences();
        $this->assertCount(1, $references);
        $this->assertSame('1', $references[0]->getResourceId());
        $this->assertSame(PageInterface::RESOURCE_KEY, $references[0]->getResourceKey());
    }
}
