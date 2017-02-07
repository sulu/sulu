<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace vendor\sulu\sulu\src\Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Initialalizer;

use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InitializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var InitializerInterface
     */
    private $initializer1;

    /**
     * @var InitializerInterface
     */
    private $initializer2;

    /**
     * @var InitializerInterface
     */
    private $initializer3;

    /**
     * @var Initializer
     */
    private $initializer;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->initializer1 = $this->prophesize(InitializerInterface::class);
        $this->initializer2 = $this->prophesize(InitializerInterface::class);
        $this->initializer3 = $this->prophesize(InitializerInterface::class);

        $this->initializer = new Initializer(
            $this->container->reveal(),
            [
                'service1' => 50,
                'service2' => 10,
                'service3' => 29,
            ]
        );

        $this->container->get('service1')->willReturn($this->initializer1->reveal());
        $this->container->get('service2')->willReturn($this->initializer2->reveal());
        $this->container->get('service3')->willReturn($this->initializer3->reveal());
    }

    public function testInitialize()
    {
        $calls = [];
        $output = new NullOutput();

        $this->initializer1->initialize($output, false)->will(function () use (&$calls) {
            $calls[] = 'service1';
        });
        $this->initializer2->initialize($output, false)->will(function () use (&$calls) {
            $calls[] = 'service2';
        });
        $this->initializer3->initialize($output, false)->will(function () use (&$calls) {
            $calls[] = 'service3';
        });

        $this->initializer->initialize();

        $this->assertEquals([
            'service1', 'service3', 'service2',
        ], $calls);
    }

    public function testInitializeWithPurge()
    {
        $calls = [];
        $output = new NullOutput();

        $this->initializer1->initialize($output, true)->will(function () use (&$calls) {
            $calls[] = 'service1';
        });
        $this->initializer2->initialize($output, true)->will(function () use (&$calls) {
            $calls[] = 'service2';
        });
        $this->initializer3->initialize($output, true)->will(function () use (&$calls) {
            $calls[] = 'service3';
        });

        $this->initializer->initialize(null, true);

        $this->assertEquals(['service1', 'service3', 'service2'], $calls);
    }
}
