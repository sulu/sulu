<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration definition of sulu_audience_targeting.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_audience_targeting');

        $rootNode
            ->children()
                ->scalarNode('number_of_priorities')
                    ->defaultValue(5)
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
                        ->arrayNode('target_group')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup')
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepository')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('target_group_condition')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupCondition')
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionRepository')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('target_group_rule')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRule')
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleRepository')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('target_group_webspace')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspace')
                                ->end()
                                ->scalarNode('repository')
                                    ->defaultValue('Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepository')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
