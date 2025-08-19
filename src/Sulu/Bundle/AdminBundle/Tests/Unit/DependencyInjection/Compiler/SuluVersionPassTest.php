<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\SuluVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SuluVersionPassTest extends TestCase
{
    use ProphecyTrait;

    public function testProcess(): void
    {
        $versionPass = new SuluVersionPass();
        $container = $this->prophesize(ContainerBuilder::class);
        $container->getParameter('kernel.project_dir')->willReturn(__DIR__ . '/Resources/VersionPass');

        $container->setParameter('sulu.version', '1.5.2')->shouldBeCalled();
        $container->setParameter('app.version', '1.2.3')->shouldBeCalled();

        $versionPass->process($container->reveal());
    }
}
