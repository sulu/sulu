<?php

namespace Sulu\Bundle\CoreBundle\Cache;

use Prophecy\PhpUnit\ProphecyTestCase;

class StructureWarmerTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->structureManager = $this->prophesize('Sulu\Component\Content\StructureManagerInterface');
        $this->warmer = new StructureWarmer($this->structureManager->reveal());
    }

    public function testWarmup()
    {
        $this->structureManager->getStructures()->shouldBeCalled();
        $this->warmer->warmup('/not/important/argument');
    }
}
