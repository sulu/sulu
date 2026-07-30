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

namespace Sulu\Page\Infrastructure\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Page fixtures need the homepage of a webspace to exist, as it is the parent of all other pages. Therefore the
 * "fixtures" builder has to run after the "homepage" builder. Both builders only depend on the "database" builder,
 * which makes their order depend on the order the bundles are registered in.
 *
 * @internal
 */
final class FixturesBuilderDependencyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sulu_core.build.builder.fixtures')
            || !$container->hasDefinition('sulu_page.homepage_builder')
        ) {
            return;
        }

        $definition = $container->getDefinition('sulu_core.build.builder.fixtures');

        /** @var string[] $additionalDependencies */
        $additionalDependencies = $definition->getArguments()[0] ?? [];
        $additionalDependencies[] = 'homepage';

        $definition->setArgument(0, \array_values(\array_unique($additionalDependencies)));
    }
}
