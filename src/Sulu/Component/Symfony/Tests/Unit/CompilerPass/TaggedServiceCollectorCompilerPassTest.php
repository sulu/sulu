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

use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TaggedServiceCollectorCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition('test')->willReturn(true);
        $container->findTaggedServiceIds('test-tag')->willReturn(
            [
                'id1' => [['priority' => 10]],
                'id2' => [[], ['priority' => 20]],
            ]
        );
        $definition = new Definition('Test', [[]]);
        $container->getDefinition('test')->willReturn($definition);

        $compilerPass = new TaggedServiceCollectorCompilerPass('test', 'test-tag');
        $compilerPass->process($container->reveal());
        $result = $definition->getArgument(0);

        $this->assertCount(3, $result);
        $this->assertEquals('id2', $result[0]->__toString());
        $this->assertEquals('id1', $result[1]->__toString());
        $this->assertEquals('id2', $result[2]->__toString());
    }

    public function testProcessDifferentArgument()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition('test')->willReturn(true);
        $container->findTaggedServiceIds('test-tag')->willReturn(
            [
                'id1' => [['priority' => 10]],
                'id2' => [[], ['priority' => 20]],
            ]
        );
        $definition = new Definition('Test', [10, []]);
        $container->getDefinition('test')->willReturn($definition);

        $compilerPass = new TaggedServiceCollectorCompilerPass('test', 'test-tag', 1);
        $compilerPass->process($container->reveal());
        $result = $definition->getArgument(1);

        $this->assertEquals(10, $definition->getArgument(0));
        $this->assertCount(3, $result);
    }

    public function testProcessNoDefinition()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition('test')->willReturn(false);
        $container->findTaggedServiceIds('test-tag')->shouldNotBeCalled();
        $container->getDefinition('test')->shouldNotBeCalled();

        $compilerPass = new TaggedServiceCollectorCompilerPass('test', 'test-tag');
        $compilerPass->process($container->reveal());
    }

    public function testProcessWithAlias()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->hasDefinition('test')->willReturn(true);
        $container->findTaggedServiceIds('test-tag')->willReturn(
            [
                'id1' => [['priority' => 10, 'alias' => 't1']],
                'id2' => [['alias' => 't2'], ['priority' => 20, 'alias' => 't3']],
            ]
        );
        $definition = new Definition('Test', [[]]);
        $container->getDefinition('test')->willReturn($definition);

        $compilerPass = new TaggedServiceCollectorCompilerPass('test', 'test-tag', 0, 'alias');
        $compilerPass->process($container->reveal());
        $result = $definition->getArgument(0);

        $this->assertCount(3, $result);
        $this->assertEquals('id2', $result['t2']->__toString());
        $this->assertEquals('id1', $result['t1']->__toString());
        $this->assertEquals('id2', $result['t3']->__toString());
    }
}
