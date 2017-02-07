<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\Tests\Unit;

use Sulu\Component\PHPCR\PathCleanup;
use Sulu\Component\PHPCR\PathCleanupInterface;

class PathCleanupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PathCleanupInterface
     */
    private $cleaner;

    protected function setUp()
    {
        $this->cleaner = new PathCleanup(
            [
                'default' => [
                    ' ' => '-',
                    '+' => '-',
                    '.' => '-',
                ],
                'de' => [
                    'ä' => 'ae',
                    'ö' => 'oe',
                    'ü' => 'ue',
                    'Ä' => 'ae',
                    'Ö' => 'oe',
                    'Ü' => 'ue',
                    'ß' => 'ss',
                    '&' => 'und',
                ],
                'en' => [
                    '&' => 'and',
                ],
            ]
        );
    }

    public function testCleanup()
    {
        $clean = $this->cleaner->cleanup('-/aSDf     asdf/äöü-', 'de');

        $this->assertEquals('/asdf-asdf/aeoeue', $clean);
    }

    public function testValidate()
    {
        $this->assertFalse($this->cleaner->validate('-/aSDf     asdf/äöü-'));
        $this->assertTrue($this->cleaner->validate('/asdf/asdf'));
        $this->assertFalse($this->cleaner->validate('  '));
        $this->assertFalse($this->cleaner->validate('/Test'));
        $this->assertFalse($this->cleaner->validate('/-test'));
        $this->assertFalse($this->cleaner->validate('/asdf.xml'));
    }
}
