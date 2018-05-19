<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\DocumentHelper;

class DocumentHelperTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->document = new \stdClass();
        $this->titleDocument = $this->prophesize(TitleBehavior::class);
    }

    /**
     * It should return a debug title for a document.
     */
    public function testDebugTitle()
    {
        $title = DocumentHelper::getDebugTitle($this->document);
        $this->assertEquals(32, strlen($title));
    }

    /**
     * It should show the title for a document which implements the TitleBehavior.
     */
    public function testDebugTitleWithTitle()
    {
        $this->titleDocument->getTitle()->willReturn('Hello');
        $title = DocumentHelper::getDebugTitle($this->titleDocument->reveal());
        $this->assertContains('Hello', $title);
    }
}
