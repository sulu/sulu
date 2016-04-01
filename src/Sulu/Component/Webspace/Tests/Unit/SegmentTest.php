<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Sulu\Component\Webspace\Segment;

class SegmentTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->segment = new Segment();
    }

    public function testToArray()
    {
        $expected = [
            'key' => 'foo',
            'name' => 'ello',
            'default' => 'def',
        ];

        $this->segment->setKey($expected['key']);
        $this->segment->setName($expected['name']);
        $this->segment->setDefault($expected['default']);

        $this->assertEquals($expected, $this->segment->toArray());
    }
}
