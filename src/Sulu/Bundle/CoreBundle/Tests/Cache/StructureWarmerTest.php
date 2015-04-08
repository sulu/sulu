<?php

namespace Sulu\Bundle\CoreBundle\Tests\Cache;

use Sulu\Bundle\CoreBundle\Cache\StructureWarmer;

class StructureWarmerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->structureFactory = $this->prophesize('Sulu\Component\Structure\Factory\StructureFactoryInterface');
        $this->warmer = new StructureWarmer($this->structureFactory->reveal());
    }

    public function testWarmup()
    {
        $this->structureFactory->getStructures('page')->shouldBeCalled();
        $this->structureFactory->getStructures('snippet')->shouldBeCalled();
        $this->warmer->warmup('/not/important/argument');
    }
}
