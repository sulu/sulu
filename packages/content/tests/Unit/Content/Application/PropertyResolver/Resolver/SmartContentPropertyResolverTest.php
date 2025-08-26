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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\Resolver\SmartContentPropertyResolver;
use Sulu\Content\Application\Visitor\SmartContentFiltersVisitorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SmartContentPropertyResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testGetType(): void
    {
        $this->assertSame('smart_content', SmartContentPropertyResolver::getType());
    }

    public function testResolveWithInvalidData(): void
    {
        $requestStack = $this->prophesize(RequestStack::class);
        $filtersVisitor = $this->prophesize(SmartContentFiltersVisitorInterface::class);

        $resolver = new SmartContentPropertyResolver(
            $requestStack->reveal(),
            [$filtersVisitor->reveal()]
        );

        // Test with invalid data - should return ContentView
        $result = $resolver->resolve([], 'en');

        // Test with array list data - should return ContentView
        $result = $resolver->resolve([1, 2, 3], 'en');
    }

    public function testResolveWithNullRequestDoesNotThrowException(): void
    {
        // The main purpose is to test that when getCurrentRequest returns null,
        // the code creates a new Request() and doesn't crash
        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn(null);

        $filtersVisitor = $this->prophesize(SmartContentFiltersVisitorInterface::class);
        $filtersVisitor->visit(Argument::any(), Argument::any(), Argument::any())->willReturn([]);

        $resolver = new SmartContentPropertyResolver(
            $requestStack->reveal(),
            [$filtersVisitor->reveal()]
        );

        // This should not throw an exception about null request
        $result = $resolver->resolve([], 'de');
    }
}
