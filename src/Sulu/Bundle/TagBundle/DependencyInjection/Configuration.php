<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\DependencyInjection;

use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TagBundle\Entity\TagRepository;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Build tree for tag-bundle.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('sulu_tag')
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('tag')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Tag::class)->end()
                                ->scalarNode('repository')->defaultValue(TagRepository::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('content')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('types')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('tag_list')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('template')
                                            ->defaultValue('SuluTagBundle:Template:content-types/tag_list.html.twig')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
