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

namespace Sulu\Content\Tests\Unit\Application\Visitor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\Visitor\ExcludeSelfSmartContentFiltersVisitor;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ExcludeSelfSmartContentFiltersVisitorTest extends TestCase
{
    use ProphecyTrait;

    public function testVisitAddsCurrentContentIdToExcluded(): void
    {
        $resource = $this->prophesize(ContentRichEntityInterface::class);
        $resource->getId()->willReturn('current-page');
        $object = $this->prophesize(DimensionContentInterface::class);
        $object->getResource()->willReturn($resource->reveal());

        $request = new Request();
        $request->attributes->set('object', $object->reveal());

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $visitor = new ExcludeSelfSmartContentFiltersVisitor($requestStack);

        $result = $visitor->visit([], ['excluded' => ['other-page']], []);

        $this->assertSame(['other-page', 'current-page'], $result['excluded']);
    }

    public function testVisitDeduplicatesTheCurrentContentId(): void
    {
        $resource = $this->prophesize(ContentRichEntityInterface::class);
        $resource->getId()->willReturn('current-page');
        $object = $this->prophesize(DimensionContentInterface::class);
        $object->getResource()->willReturn($resource->reveal());

        $request = new Request();
        $request->attributes->set('object', $object->reveal());

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $visitor = new ExcludeSelfSmartContentFiltersVisitor($requestStack);

        $result = $visitor->visit([], ['excluded' => ['current-page']], []);

        $this->assertSame(['current-page'], $result['excluded']);
    }

    public function testVisitWithoutRequestReturnsFiltersUnchanged(): void
    {
        $visitor = new ExcludeSelfSmartContentFiltersVisitor(new RequestStack());

        $filters = ['excluded' => []];
        $result = $visitor->visit([], $filters, []);

        $this->assertSame($filters, $result);
    }

    public function testVisitWithoutCurrentObjectReturnsFiltersUnchanged(): void
    {
        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $visitor = new ExcludeSelfSmartContentFiltersVisitor($requestStack);

        $filters = ['excluded' => []];
        $result = $visitor->visit([], $filters, []);

        $this->assertSame($filters, $result);
    }
}
