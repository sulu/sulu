<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\SegmentRequestProcessor;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class SegmentRequestProcessorTest extends TestCase
{
    /**
     * @var SegmentRequestProcessor
     */
    private $segmentRequestProcessor;

    public function setUp(): void
    {
        $this->segmentRequestProcessor = new SegmentRequestProcessor();
    }

    public function testProcessWithoutSegmentValue()
    {
        $request = new Request();

        $webspace = new Webspace();
        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace
        );

        $attributes = $this->segmentRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertNull($attributes->getAttribute('segment'));
    }

    public function provideProcessWithDefaultSegmentValue()
    {
        return [
            ['s'],
            ['w'],
        ];
    }

    /**
     * @dataProvider provideProcessWithDefaultSegmentValue
     */
    public function testProcessWithDefaultSegmentValue($segmentKey)
    {
        $request = new Request();

        $webspace = new Webspace();
        $segment = new Segment();
        $segment->setKey($segmentKey);
        $webspace->setDefaultSegment($segment);
        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace
        );

        $attributes = $this->segmentRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertEquals($segmentKey, $attributes->getAttribute('segment')->getKey());
    }
}
