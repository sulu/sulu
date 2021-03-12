<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\DependencyInjection;

use Sulu\Bundle\EventLogBundle\Entity\EventRecord;
use Sulu\Bundle\EventLogBundle\Entity\EventRecordRepository;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sulu_event_log');
        $rootNode = $treeBuilder->getRootNode();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    private function addObjectsSection(ArrayNodeDefinition $node)
    {
        $node->children()
            ->arrayNode('objects')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('event_record')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('model')->defaultValue(EventRecord::class)->end()
                            ->scalarNode('repository')->defaultValue(EventRecordRepository::class)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
