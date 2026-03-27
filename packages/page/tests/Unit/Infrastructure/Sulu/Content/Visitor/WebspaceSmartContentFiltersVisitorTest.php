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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Content\Visitor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Infrastructure\Sulu\Content\Visitor\WebspaceSmartContentFiltersVisitor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebspaceSmartContentFiltersVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private ObjectProphecy $requestAnalyzer;
    /**
     * @var ObjectProphecy<RequestStack>
     */
    private ObjectProphecy $requestStack;
    private WebspaceSmartContentFiltersVisitor $visitor;

    protected function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->visitor = new WebspaceSmartContentFiltersVisitor(
            $this->requestAnalyzer->reveal(),
            $this->requestStack->reveal()
        );
    }

    public function testVisitAddsWebspaceKeyToFilters(): void
    {
        $request = $this->prophesize(Request::class);
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('example');

        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $filters = ['locale' => 'en'];
        $result = $this->visitor->visit([], $filters, []);

        $this->assertArrayHasKey('webspaceKey', $result);
        $this->assertEquals('example', $result['webspaceKey']);
        $this->assertEquals('en', $result['locale']);
    }

    public function testVisitWithoutRequestReturnsFiltersUnchanged(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $filters = ['locale' => 'en'];
        $result = $this->visitor->visit([], $filters, []);

        $this->assertEquals($filters, $result);
        $this->assertArrayNotHasKey('webspaceKey', $result);
    }

    public function testVisitWithoutWebspaceReturnsFiltersUnchanged(): void
    {
        $request = $this->prophesize(Request::class);

        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn(null);

        $filters = ['locale' => 'en'];
        $result = $this->visitor->visit([], $filters, []);

        $this->assertEquals($filters, $result);
        $this->assertArrayNotHasKey('webspaceKey', $result);
    }

    public function testVisitPreservesExistingFilters(): void
    {
        $request = $this->prophesize(Request::class);
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu-io');

        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $filters = [
            'locale' => 'de',
            'tags' => ['tag1', 'tag2'],
            'categories' => [1, 2, 3],
        ];

        $result = $this->visitor->visit([], $filters, []);

        $this->assertEquals('sulu-io', $result['webspaceKey']);
        $this->assertEquals('de', $result['locale']);
        $this->assertEquals(['tag1', 'tag2'], $result['tags']);
        $this->assertEquals([1, 2, 3], $result['categories']);
    }

    public function testVisitWithIgnoreWebspacesSkipsWebspaceKey(): void
    {
        $request = $this->prophesize(Request::class);
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('example');

        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $filters = ['locale' => 'en'];
        $parameters = ['ignoreWebspaces' => 'true'];
        $result = $this->visitor->visit([], $filters, $parameters);

        $this->assertArrayNotHasKey('webspaceKey', $result);
        $this->assertEquals('en', $result['locale']);
    }

    public function testVisitWithIgnoreWebspacesFalseAddsWebspaceKey(): void
    {
        $request = $this->prophesize(Request::class);
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('example');

        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $filters = ['locale' => 'en'];
        $parameters = ['ignoreWebspaces' => 'false'];
        $result = $this->visitor->visit([], $filters, $parameters);

        $this->assertArrayHasKey('webspaceKey', $result);
        $this->assertEquals('example', $result['webspaceKey']);
    }

    public function testVisitWithIgnoreWebspacesBoolTrueSkipsWebspaceKey(): void
    {
        $request = $this->prophesize(Request::class);
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('example');

        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $filters = ['locale' => 'en'];
        $parameters = ['ignoreWebspaces' => true];
        $result = $this->visitor->visit([], $filters, $parameters);

        $this->assertArrayNotHasKey('webspaceKey', $result);
    }
}
