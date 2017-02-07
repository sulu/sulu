<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
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

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `objects` section.
     *
     * @param ArrayNodeDefinition $node
     */
    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('user')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\User')->end()
                                ->scalarNode('repository')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\UserRepository')->end()
                            ->end()
                        ->end()
                        ->arrayNode('role')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\Role')->end()
                                ->scalarNode('repository')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\RoleRepository')->end()
                            ->end()
                        ->end()
                        ->arrayNode('role_setting')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\RoleSetting')->end()
                                ->scalarNode('repository')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\RoleSettingRepository')->end()
                            ->end()
                        ->end()
                        ->arrayNode('access_control')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\AccessControl')->end()
                                ->scalarNode('repository')->defaultValue('Sulu\Bundle\SecurityBundle\Entity\AccessControlRepository')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
