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

use Sulu\Page\Infrastructure\Sulu\Build\HomepageBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;

/**
 * Page fixtures need the homepage of a webspace to exist, as it is the parent of all other pages. Therefore the
 * "fixtures" builder has to run after the "homepage" builder. Both builders only depend on the "database" builder,
 * which makes their order depend on the order the bundles are registered in.
 *
 * Runs as an optimization pass (after Symfony has resolved named arguments) so that argument 0 of the fixtures
 * builder definition is always addressed positionally, regardless of how it was originally configured.
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

        $additionalDependencies = $definition->getArguments()[0] ?? [];

        Assert::isArray($additionalDependencies, 'Expected argument 0 of service "sulu_core.build.builder.fixtures" to be an array.');
        Assert::allString($additionalDependencies, 'Expected argument 0 of service "sulu_core.build.builder.fixtures" to contain only strings.');

        $additionalDependencies[] = HomepageBuilder::NAME;

        $definition->setArgument(0, \array_values(\array_unique($additionalDependencies)));
    }
}
