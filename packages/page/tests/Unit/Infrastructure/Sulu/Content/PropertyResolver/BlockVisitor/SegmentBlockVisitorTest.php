<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor\SegmentBlockVisitor;

class SegmentBlockVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var SegmentBlockVisitor
     */
    private $segmentBlockVisitor;

    public function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->segmentBlockVisitor = new SegmentBlockVisitor($this->requestAnalyzer->reveal());
    }

    public function testShouldNotSkipWithObjectAsSettings(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => new \stdClass()];

        $this->assertEquals($block, $this->segmentBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => []];

        $this->assertEquals($block, $this->segmentBlockVisitor->visit($block));
    }

    public function testShouldSkipWithOtherSegment(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]];

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertNull($this->segmentBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithSameSegment(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]];

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('w');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($block, $this->segmentBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithoutSegment(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]];

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getSegment()->willReturn(null);

        $this->assertEquals($block, $this->segmentBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithoutSegmentForWebspace(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]];

        $webspace = new Webspace();
        $webspace->setKey('sulu_blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($block, $this->segmentBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithDisabledSegment(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['segment_enabled' => false, 'segments' => ['sulu_io' => 'w']]];

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($block, $this->segmentBlockVisitor->visit($block));
    }

    public function testShouldSkipWithoutSegmentEnabledFlag(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['segments' => ['sulu_io' => 'w']]];

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($block, $this->segmentBlockVisitor->visit($block));
    }
}
