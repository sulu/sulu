<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\PropertyResolver\BlockVisitor;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Content\Application\PropertyResolver\BlockVisitor\HiddenBlockVisitor;

class HiddenBlockVisitorTest extends TestCase
{
    /**
     * @var HiddenBlockVisitor
     */
    private $hiddenBlockVisitor;

    public function setUp(): void
    {
        $this->hiddenBlockVisitor = new HiddenBlockVisitor();
    }

    public function testShouldNotSkipWithObjectAsSettings(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => new \stdClass()];

        $this->assertEquals($block, $this->hiddenBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => []];

        $this->assertEquals($block, $this->hiddenBlockVisitor->visit($block));
    }

    public function testShouldSkipWithHiddenSetting(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['hidden' => true]];

        $this->assertNull($this->hiddenBlockVisitor->visit($block));
    }

    public function testShouldNotSkipWithHiddenSetting(): void
    {
        $block = ['name' => 'type1', 'metadata' => new Metadata([]), 'settings' => ['hidden' => false]];

        $this->assertEquals($block, $this->hiddenBlockVisitor->visit($block));
    }
}
