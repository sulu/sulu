<?php

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sulu_document_manager');
        $rootNode
            ->children()
                ->arrayNode('namespace')
                ->useAttributeAsKey('role')
                    ->defaultValue(array(
                        'system' => null,
                        'system_localized' => 'i18n',
                        'content' => null,
                        'content_localized' => 'i18n',
                    ))
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('mapping')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('class')
                            ->info('Fully qualified class name for mapped object')
                            ->isRequired()
                        ->end()
                        ->scalarNode('phpcr_type')
                            ->info('PHPCR type to map to')
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

