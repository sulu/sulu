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

namespace Sulu\Route\Tests\Unit\Application\ResourceLocator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Route\Application\ResourceLocator\Exception\InvalidRouteSchemaException;
use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanupInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorRequest;
use Sulu\Route\Application\ResourceLocator\RouteSchemaEvaluator;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(RouteSchemaEvaluator::class)]
class RouteSchemaEvaluatorTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<TranslatorInterface> */
    private ObjectProphecy $translator;
    /** @var ObjectProphecy<PathCleanupInterface> */
    private ObjectProphecy $pathCleanup;
    private RouteSchemaEvaluator $processor;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->pathCleanup = $this->prophesize(PathCleanupInterface::class);

        $this->processor = new RouteSchemaEvaluator(
            $this->translator->reveal(),
            $this->pathCleanup->reveal(),
        );
    }

    public function testProcessSimplePropertyAccess(): void
    {
        $request = new ResourceLocatorRequest(
            parts: ['title' => 'Hello World'],
            locale: 'en',
            webspace: null,
            resourceKey: 'articles',
            resourceId: '123',
            parentResourceId: null,
            parentResourceKey: null,
            routeSchema: '/{object[\'title\']}',
        );

        $this->pathCleanup->cleanup('Hello World', 'en')->willReturn('hello-world');

        $result = $this->processor->evaluate($request);

        $this->assertSame('/hello-world', $result);
    }

    public function testProcessWithTranslation(): void
    {
        $request = new ResourceLocatorRequest(
            parts: ['title' => 'My Article'],
            locale: 'de',
            webspace: null,
            resourceKey: 'articles',
            resourceId: '123',
            parentResourceId: null,
            parentResourceKey: null,
            routeSchema: '/{translator.trans(\'articles\', [], \'admin\')}/{object[\'title\']}',
        );

        $this->translator->trans('articles', [], 'admin')->willReturn('Artikel');
        $this->pathCleanup->cleanup('Artikel', 'de')->willReturn('artikel');
        $this->pathCleanup->cleanup('My Article', 'de')->willReturn('my-article');

        $result = $this->processor->evaluate($request);

        $this->assertSame('/artikel/my-article', $result);
    }

    public function testProcessWithImplode(): void
    {
        $request = new ResourceLocatorRequest(
            parts: ['part1' => 'Hello', 'part2' => 'World'],
            locale: 'en',
            webspace: null,
            resourceKey: 'articles',
            resourceId: '123',
            parentResourceId: null,
            parentResourceKey: null,
            routeSchema: '/{implode(\'-\', object)}',
        );

        $this->pathCleanup->cleanup('Hello-World', 'en')->willReturn('hello-world');

        $result = $this->processor->evaluate($request);

        $this->assertSame('/hello-world', $result);
    }

    public function testProcessCombinedExpressions(): void
    {
        $request = new ResourceLocatorRequest(
            parts: ['year' => '2024', 'title' => 'My Great Article'],
            locale: 'en',
            webspace: null,
            resourceKey: 'articles',
            resourceId: '123',
            parentResourceId: null,
            parentResourceKey: null,
            routeSchema: '/blog/{object[\'year\']}/{object[\'title\']}',
        );

        $this->pathCleanup->cleanup('2024', 'en')->willReturn('2024');
        $this->pathCleanup->cleanup('My Great Article', 'en')->willReturn('my-great-article');

        $result = $this->processor->evaluate($request);

        $this->assertSame('/blog/2024/my-great-article', $result);
    }

    public function testProcessInvalidSyntaxThrowsException(): void
    {
        $request = new ResourceLocatorRequest(
            parts: ['title' => 'Hello'],
            locale: 'en',
            webspace: null,
            resourceKey: 'articles',
            resourceId: '123',
            parentResourceId: null,
            parentResourceKey: null,
            routeSchema: '/{object:title}',
        );

        $this->expectException(InvalidRouteSchemaException::class);
        $this->expectExceptionMessageMatches('/Invalid expression/');

        $this->processor->evaluate($request);
    }

    public function testProcessMissingLeadingSlashThrowsException(): void
    {
        $request = new ResourceLocatorRequest(
            parts: ['title' => 'Hello'],
            locale: 'en',
            webspace: null,
            resourceKey: 'articles',
            resourceId: '123',
            parentResourceId: null,
            parentResourceKey: null,
            routeSchema: '{object[\'title\']}',
        );

        $this->pathCleanup->cleanup('Hello', 'en')->willReturn('hello');

        $this->expectException(InvalidRouteSchemaException::class);
        $this->expectExceptionMessage('must produce a path starting with /');

        $this->processor->evaluate($request);
    }

    public function testProcessDotNotation(): void
    {
        $request = new ResourceLocatorRequest(
            parts: ['title' => 'Hello'],
            locale: 'en',
            webspace: null,
            resourceKey: 'articles',
            resourceId: '123',
            parentResourceId: null,
            parentResourceKey: null,
            routeSchema: '/{object.title}',
        );

        $this->pathCleanup->cleanup('Hello', 'en')->willReturn('hello');

        $result = $this->processor->evaluate($request);

        $this->assertSame('/hello', $result);
    }
}
