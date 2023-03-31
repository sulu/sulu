<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\DependencyInjection;

use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMeta;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepository;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepository;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepository;
use Sulu\Bundle\CategoryBundle\Entity\Keyword;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepository;
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
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sulu_category');
        $rootNode = $treeBuilder->getRootNode();

        $this->addObjectsSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `objects` section.
     *
     * @return void
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
                                ->scalarNode('model')->defaultValue(Category::class)->end()
                                ->scalarNode('repository')->defaultValue(CategoryRepository::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('category_meta')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(CategoryMeta::class)->end()
                                ->scalarNode('repository')->defaultValue(CategoryMetaRepository::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('category_translation')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(CategoryTranslation::class)->end()
                                ->scalarNode('repository')->defaultValue(CategoryTranslationRepository::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('keyword')
                            ->addDefaultsIfNotSet()
                            ->children()
                                    ->scalarNode('model')->defaultValue(Keyword::class)->end()
                                    ->scalarNode('repository')->defaultValue(KeywordRepository::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
