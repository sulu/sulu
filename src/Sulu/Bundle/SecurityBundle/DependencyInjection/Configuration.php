<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('sulu_security');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('system')
                    ->defaultValue('Sulu')
                    ->setDeprecated(
                        'sulu/sulu',
                        '2.1.0',
                        'The %node% option is deprecated and will be removed. Setting this option in the admin context will break the permissions registered by the bundles.'
                    )
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
                ->arrayNode('password_policy')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('pattern')
                            ->cannotBeEmpty()
                            ->defaultValue('.{8,}')
                        ->end()
                        ->scalarNode('info_translation_key')
                            ->cannotBeEmpty()
                            ->defaultValue('sulu_security.password_policy_information')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('reset_password')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mail')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('token_send_limit')
                                    ->min(1)
                                    ->defaultValue(3)
                                ->end()
                                ->scalarNode('sender')
                                    ->defaultValue('')
                                ->end()
                                ->scalarNode('subject')
                                    ->cannotBeEmpty()
                                    ->defaultValue('sulu_security.reset_mail_subject')
                                ->end()
                                ->scalarNode('template')
                                    ->cannotBeEmpty()
                                    ->defaultValue('@SuluSecurity/mail_templates/reset_password.html.twig')
                                ->end()
                                ->scalarNode('translation_domain')
                                    ->cannotBeEmpty()
                                    ->defaultValue('admin')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('2fa')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('force')
                            ->info('Force 2FA for all or only specific users.')
                            ->canBeEnabled()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('email_regex')
                                    ->cannotBeEmpty()
                                    ->defaultValue('')
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
