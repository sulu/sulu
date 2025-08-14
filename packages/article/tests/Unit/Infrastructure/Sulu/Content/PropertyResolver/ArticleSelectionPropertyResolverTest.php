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

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Article\Infrastructure\Sulu\Content\PropertyResolver\ArticleSelectionPropertyResolver;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

#[CoversClass(ArticleSelectionPropertyResolver::class)]
class ArticleSelectionPropertyResolverTest extends TestCase
{
    private ArticleSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new ArticleSelectionPropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertEmpty($contentView->getContent());
        $this->assertSame(['ids' => []], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertEmpty($contentView->getContent());
        $this->assertSame([
            'ids' => [],
            'custom' => 'params',
        ], $contentView->getView());
    }

    /**
     * @param mixed[] $data
     */
    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertEmpty($contentView->getContent());
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
        yield 'ids_null' => [['ids' => null]];
        yield 'ids_list' => [['ids' => [1, 2]]];
        yield 'id_list' => [['id' => [1, 2]]];
    }

    /**
     * @param array{
     *     ids?: array<string|int>,
     * } $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertIsArray($content);

        foreach (($data['ids'] ?? []) as $key => $value) {
            $resolvable = $content[$key] ?? null;
            $this->assertInstanceOf(ResolvableResource::class, $resolvable);
            $this->assertSame($value, $resolvable->getId());
            $this->assertSame('article', $resolvable->getResourceLoaderKey());
        }

        $this->assertSame([
            'ids' => $data,
        ], $contentView->getView());
    }

    /**
     * @return iterable<mixed[]>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'empty' => [[]];
        yield 'string_id' => [['1', '2', '3']];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve(['1'], 'en', ['resourceLoader' => 'custom_article']);

        /** @var mixed[] $content */
        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content[0]);
        $this->assertSame('1', $content[0]->getId());
        $this->assertSame('custom_article', $content[0]->getResourceLoaderKey());
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
    }
}
