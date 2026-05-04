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

namespace Sulu\Notifier\Infrastructure\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_notifier');

        $treeBuilder->getRootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('channels')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->scalarPrototype()
                            ->validate()
                                ->ifTrue(static fn ($value): bool => !\is_string($value) || !\class_exists($value))
                                ->thenInvalid('Event class %s does not exist or is not autoloadable.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
