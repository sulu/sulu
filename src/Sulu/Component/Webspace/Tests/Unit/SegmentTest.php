<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Webspace\Segment;

class SegmentTest extends TestCase
{
    private $segment;

    public function setUp(): void
    {
        parent::setUp();

        $this->segment = new Segment();
    }

    public function testToArray(): void
    {
        $expected = [
            'key' => 'foo',
            'default' => 'def',
            'metadata' => ['title' => ['en' => 'english title', 'de' => 'german title']],
        ];

        $this->segment->setKey($expected['key']);
        $this->segment->setMetadata($expected['metadata']);
        $this->segment->setDefault($expected['default']);

        $this->assertEquals($expected, $this->segment->toArray('en'));
    }
}
