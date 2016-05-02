<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_preview');

        // add config preview interval
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('analytics_key')->defaultValue('UA-SULU-PREVIEW-KEY')->end()
                    ->end()
                ->end()
                ->scalarNode('mode')
                    ->defaultValue('auto')
                    ->validate()
                        ->ifNotInArray(['auto', 'on_request', 'off'])
                        ->thenInvalid('Invalid preview mode "%s" use one of [auto, on_request, off]')
                    ->end()
                ->end()
                ->scalarNode('delay')
                    ->defaultValue(500)
                    ->info('Used for the delayed send of changes')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
