<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application;

use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel in which the `examples` resource declares no security context, as a content type of a
 * bundle that secures nothing.
 */
class WithoutSecurityContextKernel extends Kernel
{
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                /** @var array<string, array<string, mixed>> $resources */
                $resources = $container->getParameter('sulu_admin.resources');
                unset($resources[Example::RESOURCE_KEY]['security_context']);
                $container->setParameter('sulu_admin.resources', $resources);
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }
}
