<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Infrastructure\Symfony\DependencyInjection;

use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecord;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sulu_event_log');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->arrayNode('storage')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('adapter')->defaultValue('doctrine')->end()
                    ->booleanNode('persist_payload')->defaultValue(false)->end()
                ->end()
            ->end();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    private function addObjectsSection(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('objects')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('event_record')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('model')->defaultValue(EventRecord::class)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
