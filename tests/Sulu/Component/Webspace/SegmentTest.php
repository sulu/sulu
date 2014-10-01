<?php

namespace Sulu\Component\Segment;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Component\Webspace\Segment;

class SegmentTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->segment = new Segment();
    }

    public function testToArray()
    {
        $expected = array(
            'key' => 'foo',
            'name' => 'ello',
            'default' => 'def',
        );

        $this->segment->setKey($expected['key']);
        $this->segment->setName($expected['name']);
        $this->segment->setDefault($expected['default']);

        $this->assertEquals($expected, $this->segment->toArray());
    }
}
