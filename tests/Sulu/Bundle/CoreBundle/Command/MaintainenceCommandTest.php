<?php

namespace Sulu\Bundle\CoreBundle\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Prophecy\PhpUnit\ProphecyTestCase;
use Prophecy\Argument;

class MaintainenceCommandTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->manager = $this->prophesize('Sulu\Component\Maintenance\MaintenanceManager');
        $this->maintainer1 = $this->prophesize('Sulu\Component\Maintenance\MaintainerInterface');
        $this->maintainer1->getName()->willReturn('maintainer-1');

        $this->container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->get('sulu.maintenance_manager')->willReturn($this->manager);

        $this->manager->getMaintainer('maintainer-1')->willReturn($this->maintainer1);
        $this->manager->getMaintainers()->willReturn(array(
            $this->maintainer1->reveal()
        ));

        $this->command = new MaintenanceCommand();
        $this->command->setContainer($this->container->reveal());
        $this->tester = new CommandTester($this->command);
    }

    public function testList()
    {
        $this->tester->execute(array(
            '--list' => true
        ));
    }

    public function testMaintainByName()
    {
        $this->manager->getMaintainer('maintainer-1')->shouldBeCalled();
        $this->maintainer1->maintain(Argument::type('Symfony\Component\Console\Output\OutputInterface'))->shouldBeCalled();
        $this->tester->execute(array(
            'name' => 'maintainer-1',
        ));
    }


    public function testMaintainAll()
    {
        $this->manager->getMaintainers()->shouldBeCalled();
        $this->maintainer1->maintain(Argument::type('Symfony\Component\Console\Output\OutputInterface'))->shouldBeCalled();
        $this->tester->execute(array());
    }
}
