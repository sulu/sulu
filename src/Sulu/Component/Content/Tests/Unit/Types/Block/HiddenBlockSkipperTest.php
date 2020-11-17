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
use Sulu\Component\Content\Types\Block\HiddenBlockSkipper;

class HiddenBlockSkipperTest extends TestCase
{
    /**
     * @var HiddenBlockSkipper
     */
    private $hiddenBlockSkipper;

    public function setUp(): void
    {
        $this->hiddenBlockSkipper = new HiddenBlockSkipper();
    }

    public function testShouldNotSkipWithObjectAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(new \stdClass());

        $this->assertFalse($this->hiddenBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings([]);

        $this->assertFalse($this->hiddenBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldSkipWithHiddenSetting()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['hidden' => true]);

        $this->assertTrue($this->hiddenBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithHiddenSetting()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(['hidden' => false]);

        $this->assertFalse($this->hiddenBlockSkipper->shouldSkip($blockPropertyType));
    }
}
