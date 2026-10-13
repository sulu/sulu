<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\FlysystemV3CompilerPass;
use Sulu\Bundle\MediaBundle\SuluMediaBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SuluMediaBundleTest extends TestCase
{
    public function testBuildRegistersFlysystemV3CompilerPass(): void
    {
        $container = new ContainerBuilder();

        (new SuluMediaBundle())->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $this->assertContainsOnlyInstancesOf(\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface::class, $passes);
        $this->assertNotEmpty(\array_filter($passes, static fn ($pass) => $pass instanceof FlysystemV3CompilerPass));
    }
}
