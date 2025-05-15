<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types\Block;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Types\Block\SegmentBlockVisitor;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;

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
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(new \stdClass());

        $this->assertEquals($blockPropertyType, $this->segmentBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings([]);

        $this->assertEquals($blockPropertyType, $this->segmentBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldSkipWithOtherSegment(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertNull($this->segmentBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithSameSegment(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('w');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($blockPropertyType, $this->segmentBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithoutSegment(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getSegment()->willReturn(null);

        $this->assertEquals($blockPropertyType, $this->segmentBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithoutSegmentForWebspace(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($blockPropertyType, $this->segmentBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldNotSkipWithDisabledSegment(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => false, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($blockPropertyType, $this->segmentBlockVisitor->visit($blockPropertyType));
    }

    public function testShouldSkipWithoutSegmentEnabledFlag(): void
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertEquals($blockPropertyType, $this->segmentBlockVisitor->visit($blockPropertyType));
    }
}
