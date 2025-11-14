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

namespace Sulu\Bundle\Page\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageTreeRoutePropertyResolver;

#[CoversClass(PageTreeRoutePropertyResolver::class)]
class PageTreeRoutePropertyResolverTest extends TestCase
{
    use ProphecyTrait;

    private PageTreeRoutePropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new PageTreeRoutePropertyResolver();
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame(null, $contentView->getContent());
        $this->assertSame([], $contentView->getView());
    }

    public function testResolveNull(): void
    {
        $contentView = $this->resolver->resolve([], 'en');

        $this->assertSame(null, $contentView->getContent());
        $this->assertSame([], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve([], 'en', ['custom' => 'params']);

        $this->assertSame(null, $contentView->getContent());
        $this->assertSame([
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame(null, $contentView->getContent());
        $this->assertSame([], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: mixed,
     * }>
     */
    public static function provideUnresolvableData(): iterable
    {
        yield 'null' => [null];
        yield 'page_not_array' => [['page' => 'test']];
        yield 'page_without_path' => [['page' => [], 'suffix' => '/test']];
        yield 'page_with_path_null' => [['page' => ['path' => null], 'suffix' => '/test']];
        yield 'page_with_path_int' => [['page' => ['path' => 1], 'suffix' => '/test']];
        yield 'suffix_not_defined' => [['page' => ['path' => '/parent']]];
        yield 'suffix_null' => [['page' => ['path' => '/parent'], 'suffix' => null]];
        yield 'suffix_int' => [['page' => ['path' => '/parent'], 'suffix' => 1]];
        yield 'single_value' => [1];
        yield 'object' => [(object) [1, 2]];
    }

    /**
     * @param array{
     *     page: array{
     *         uuid: string,
     *         path: string,
     *     },
     *     suffix: string,
     * } $data
     */
    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(array $data, ?string $url): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertSame($url, $contentView->getContent());
        $this->assertSame([...$data], $contentView->getView());
    }

    /**
     * @return iterable<array{
     *     0: array{
     *         page: array{
     *             uuid: string,
     *             path: string,
     *         },
     *         suffix: string,
     *     },
     * }>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'short_page_path_and_suffix' => [['page' => ['path' => '/parent'], 'suffix' => '/article'], '/parent/article'];
        yield 'short_page_path_and_long_suffix' => [['page' => ['path' => '/parent'], 'suffix' => '/article/test'], '/parent/article/test'];
        yield 'long_page_path_and_long_suffix' => [['page' => ['path' => '/parent/page'], 'suffix' => '/article/test'], '/parent/page/article/test'];
    }
}
