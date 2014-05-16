<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\ContactBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root('sulu_contact')
            ->children()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('phoneType')->defaultValue('1')->end()
                        ->scalarNode('phoneTypeMobile')->defaultValue('3')->end()
                        ->scalarNode('phoneTypeIsdn')->defaultValue('4')->end()
                        ->scalarNode('emailType')->defaultValue('1')->end()
                        ->scalarNode('addressType')->defaultValue('1')->end()
                        ->scalarNode('urlType')->defaultValue('1')->end()
                        ->scalarNode('faxType')->defaultValue('1')->end()
                        ->scalarNode('country')->defaultValue('1')->end()
                    ->end()
                ->end()
                ->arrayNode('account_types')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('basic')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('id')->defaultValue(0)->end()
                                ->scalarNode('name')->defaultValue('basic')->end()
                                ->scalarNode('translation')->defaultValue('contact.account.type.basic')->end()
                                ->booleanNode('hasFinancials')->defaultFalse()->end()
                            ->end()
                        ->end()
                        ->arrayNode('lead')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('id')->defaultValue(1)->end()
                                ->scalarNode('name')->defaultValue('lead')->end()
                                ->scalarNode('translation')->defaultValue('contact.account.type.lead')->end()
                                ->booleanNode('hasFinancials')->defaultFalse()->end()
                            ->end()
                        ->end()
                        ->arrayNode('customer')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('id')->defaultValue(2)->end()
                                ->scalarNode('name')->defaultValue('customer')->end()
                                ->scalarNode('translation')->defaultValue('contact.account.type.customer')->end()
                                ->booleanNode('hasFinancials')->defaultTrue()->end()
                            ->end()
                        ->end()
                        ->arrayNode('supplier')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('id')->defaultValue(3)->end()
                                ->scalarNode('name')->defaultValue('supplier')->end()
                                ->scalarNode('translation')->defaultValue('contact.account.type.supplier')->end()
                                ->booleanNode('hasFinancials')->defaultTrue()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;


        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
