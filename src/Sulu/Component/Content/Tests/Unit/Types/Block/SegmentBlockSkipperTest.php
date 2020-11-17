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
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Types\Block\SegmentBlockSkipper;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;

class SegmentBlockSkipperTest extends TestCase
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var SegmentBlockSkipper
     */
    private $segmentBlockSkipper;

    public function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->segmentBlockSkipper = new SegmentBlockSkipper($this->requestAnalyzer->reveal());
    }

    public function testShouldNotSkipWithObjectAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(new \stdClass());

        $this->assertFalse($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings([]);

        $this->assertFalse($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldSkipWithOtherSegment()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertTrue($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithSameSegment()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('w');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertFalse($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithoutSegment()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getSegment()->willReturn(null);

        $this->assertFalse($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithoutSegmentForWebspace()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => true, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_blog');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertFalse($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithDisabledSegment()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segment_enabled' => false, 'segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertFalse($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldSkipWithoutSegmentEnabledFlag()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['segments' => ['sulu_io' => 'w']]);

        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->assertFalse($this->segmentBlockSkipper->shouldSkip($blockPropertyType));
    }
}
