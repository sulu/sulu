<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Symfony\Tests\Unit\CompilerPass;

use Prophecy\Argument;
use Sulu\Component\Symfony\CompilerPass\Customizer\CustomizerInterface;
use Sulu\Component\Symfony\CompilerPass\ServiceCustomizerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ServiceCustomizerCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition('service1')->willReturn(true);

        $definition = $this->prophesize(Definition::class);
        $container->getDefinition('service1')->willReturn($definition->reveal());

        $customizer = $this->prophesize(CustomizerInterface::class);

        $compilerPass = new ServiceCustomizerCompilerPass('service1');
        $compilerPass->add($customizer->reveal());

        $compilerPass->process($container->reveal());

        $customizer->customize($definition->reveal(), $container->reveal());
    }

    public function testProcessNotExists()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition('service1')->willReturn(false);
        $container->getDefinition('service1')->shouldNotBeCalled();

        $customizer = $this->prophesize(CustomizerInterface::class);

        $compilerPass = new ServiceCustomizerCompilerPass('service1');
        $compilerPass->add($customizer->reveal());

        $compilerPass->process($container->reveal());

        $customizer->customize(Argument::cetera())->shouldNotBeCalled();
    }
}
