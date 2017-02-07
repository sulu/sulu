<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_category')
            ->children()
                ->arrayNode('content')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('types')
                        ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('category_list')
                                ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('template')
                                            ->defaultValue('SuluCategoryBundle:Template:content-types/category_list.html.twig')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
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
                        ->arrayNode('category')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\Category')->end()
                                ->scalarNode('repository')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\CategoryRepository')->end()
                            ->end()
                        ->end()
                        ->arrayNode('category_meta')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\CategoryMeta')->end()
                                ->scalarNode('repository')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepository')->end()
                            ->end()
                        ->end()
                        ->arrayNode('category_translation')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation')->end()
                                ->scalarNode('repository')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepository')->end()
                            ->end()
                        ->end()
                        ->arrayNode('keyword')
                            ->addDefaultsIfNotSet()
                            ->children()
                                    ->scalarNode('model')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\Keyword')->end()
                                    ->scalarNode('repository')->defaultValue('Sulu\Bundle\CategoryBundle\Entity\KeywordRepository')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
