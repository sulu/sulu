<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\DocumentHelper;

class DocumentHelperTest extends TestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        $this->document = new \stdClass();
        $this->titleDocument = $this->prophesize(TitleBehavior::class);
    }

    /**
     * It should return a debug title for a document.
     */
    public function testDebugTitle(): void
    {
        $title = DocumentHelper::getDebugTitle($this->document);
        $this->assertEquals(32, \strlen($title));
    }

    /**
     * It should show the title for a document which implements the TitleBehavior.
     */
    public function testDebugTitleWithTitle(): void
    {
        $this->titleDocument->getTitle()->willReturn('Hello');
        $title = DocumentHelper::getDebugTitle($this->titleDocument->reveal());
        $this->assertStringContainsString('Hello', $title);
    }
}
