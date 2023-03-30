<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace vendor\sulu\sulu\src\Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Initialalizer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InitializerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContainerInterface>
     */
    private ObjectProphecy $container;

    /**
     * @var ObjectProphecy<InitializerInterface>
     */
    private ObjectProphecy $initializer1;

    /**
     * @var ObjectProphecy<InitializerInterface>
     */
    private ObjectProphecy $initializer2;

    /**
     * @var ObjectProphecy<InitializerInterface>
     */
    private ObjectProphecy $initializer3;

    private \Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer $initializer;

    public function setUp(): void
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

    public function testInitialize(): void
    {
        $calls = [];
        $output = new NullOutput();

        $this->initializer1->initialize($output, false)->will(function() use (&$calls): void {
            $calls[] = 'service1';
        });
        $this->initializer2->initialize($output, false)->will(function() use (&$calls): void {
            $calls[] = 'service2';
        });
        $this->initializer3->initialize($output, false)->will(function() use (&$calls): void {
            $calls[] = 'service3';
        });

        $this->initializer->initialize();

        $this->assertEquals([
            'service1', 'service3', 'service2',
        ], $calls);
    }

    public function testInitializeWithPurge(): void
    {
        $calls = [];
        $output = new NullOutput();

        $this->initializer1->initialize($output, true)->will(function() use (&$calls): void {
            $calls[] = 'service1';
        });
        $this->initializer2->initialize($output, true)->will(function() use (&$calls): void {
            $calls[] = 'service2';
        });
        $this->initializer3->initialize($output, true)->will(function() use (&$calls): void {
            $calls[] = 'service3';
        });

        $this->initializer->initialize(null, true);

        $this->assertEquals(['service1', 'service3', 'service2'], $calls);
    }
}
