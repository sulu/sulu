<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Symfony\Tests\Unit\CompilerPass\Customizer;

use Sulu\Component\Symfony\CompilerPass\Customizer\ReplaceClassCustomizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ReplaceClassCustomizerTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomize()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $definition = $this->prophesize(Definition::class);

        $customizer = new ReplaceClassCustomizer('\CustomClass');

        $customizer->customize($definition->reveal(), $container->reveal());

        $definition->setClass('\CustomClass')->shouldBeCalled();
    }
}
