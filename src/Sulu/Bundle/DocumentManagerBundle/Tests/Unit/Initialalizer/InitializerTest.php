<?php

namespace vendor\sulu\sulu\src\Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Initialalizer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Symfony\Component\Console\Output\NullOutput;

class InitializerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->initializer1 = $this->prophesize(InitializerInterface::class);
        $this->initializer2 = $this->prophesize(InitializerInterface::class);
        $this->initializer3 = $this->prophesize(InitializerInterface::class);

        $this->initializer = new Initializer(
            $this->container->reveal(),
            array(
                'service1' => 50,
                'service2' => 10,
                'service3' => 29,
            )
        );

        $this->container->get('service1')->willReturn($this->initializer1->reveal());
        $this->container->get('service2')->willReturn($this->initializer2->reveal());
        $this->container->get('service3')->willReturn($this->initializer3->reveal());
    }

    /**
     * It should execute the initializers in the correct order
     */
    public function testInitialize()
    {
        $calls = array();
        $out = new NullOutput();

        $this->initializer1->initialize($out)->will(function () use (&$calls) {
            $calls[] = 'service1';
        });
        $this->initializer2->initialize($out)->will(function () use (&$calls) {
            $calls[] = 'service2';
        });
        $this->initializer3->initialize($out)->will(function () use (&$calls) {
            $calls[] = 'service3';
        });

        $this->initializer->initialize();

        $this->assertEquals(array(
            'service1', 'service3', 'service2'
        ), $calls);
    }
}
