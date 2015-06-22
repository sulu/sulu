<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_security');

        $rootNode
            ->children()
                ->scalarNode('system')
                    ->defaultValue('Sulu')
                ->end()
                ->arrayNode('checker')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('security_types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('fixture')
                            ->defaultValue(__DIR__ . '/../DataFixtures/security-types.xml')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
