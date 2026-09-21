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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Kernel in which the `examples` resource key registers no workflow transition request security
 * context provider, as a content type of a bundle that does not ship one.
 */
class WithoutSecurityContextProviderKernel extends Kernel
{
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->getDefinition('example_test.example_security_context_provider')
                    ->clearTag('sulu_content.workflow_transition_request_security_context_provider');
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }
}
