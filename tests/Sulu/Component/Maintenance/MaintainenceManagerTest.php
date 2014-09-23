<?php

namespace Sulu\Component\Maintenance;

use Prophecy\PhpUnit\ProphecyTestCase;

class MaintenanceManagerTest extends ProphecyTestCase
{
    protected $maintainers = array();

    public function setUp()
    {
        parent::setUp();

        for ($i = 1; $i <= 3; $i++) {
            $this->maintainers[$i] = $this->prophesize('Sulu\Component\Maintainence\MaintainerInterface');
            $this->maintainers[$i]->getName()->willReturn('maintainer-' . $i);
        }

        $this->manager = new MaintainenceManager();
    }

    public function testGetMaintainers()
    {
        $this->manager->registerMaintainer($this->maintainers[1]->reveal());
        $this->manager->registerMaintainer($this->maintainers[2]->reveal());
        $this->manager->registerMaintainer($this->maintainers[3]->reveal());

        $res = $this->manager->getMaintainers();
        $this->assertCount(3, $res);
    }

    public function testGetMaintainer()
    {
        $this->manager->registerMaintainer($this->maintainers[1]->reveal());
        $res = $this->manager->getMaintainer('maintainer-1');
        $this->assertSame($this->maintainers[1]->reveal(), $res);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetMaintainerNotKnown()
    {
        $this->manager->getMaintainer('foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoubleRegister()
    {
        $this->manager->registerMaintainer($this->maintainers[1]->reveal());
        $this->manager->registerMaintainer($this->maintainers[1]->reveal());
    }
}
