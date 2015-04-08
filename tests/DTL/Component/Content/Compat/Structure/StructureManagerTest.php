<?php

namespace DTL\Component\Content\Compat\Stucture;

use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Component\Content\Compat\Structure\StructureManager;
use DTL\Component\Content\Structure\Structure;

class StructureManagerTest extends ProphecyTestCase
{
    public function setUp()
    {
        $this->structureFactory = $this->prophesize('DTL\Component\Content\Structure\Factory\StructureFactory');
        $this->structure1 = new Structure();
        $this->structure2 = new Structure();
        $this->compatManager = new StructureManager($this->structureFactory->reveal());
    }

    public function testGetStructure()
    {
        $this->structureFactory->getStructure('page', 'foo')->willReturn($this->structure1);
        $structure = $this->compatManager->getStructure('foo');
        $this->assertInstanceOf('DTL\Component\Content\Compat\Structure\StructureBridge', $structure);
    }

    public function getStructures()
    {
        $this->structureFactory->getStructures('page')->willReturn(array($this->structure1, $this->structure2));
        $structures = $this->compatManager->getStructures();
        $this->assertCount(2, $structures);
    }
}
