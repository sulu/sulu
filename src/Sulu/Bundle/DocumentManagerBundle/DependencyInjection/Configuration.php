<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('sulu_document_manager');
        $rootNode
            ->children()
                ->arrayNode('namespace')
                    ->useAttributeAsKey('role')
                    ->defaultValue([
                        'extension_localized' => 'i18n',
                        'system' => 'sulu',
                        'system_localized' => 'i18n',
                        'content' => null,
                        'content_localized' => 'i18n',
                    ])
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('debug')
                    ->info('Enable the debug event dispatcher. Logs all document manager events. Very slow.')
                    ->defaultValue(false)
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
                ->end()
                ->scalarNode('default_manager')
                    ->info('Name of the default document manager')
                    ->defaultValue('default')
                ->end()
                ->arrayNode('managers')
                    ->useAttributeAsKey('name')
                    ->defaultValue([
                        'default' => [
                            'session' => 'default',
                        ],
                    ])
                    ->prototype('array')
                        ->children()
                            ->scalarNode('session')
                                ->info('PHPCR session name')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
