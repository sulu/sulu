<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Container extension for sulu audience targeting.
 */
class SuluAudienceTargetingExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_audience_targeting.enabled', true);
        $container->setParameter('sulu_audience_targeting.number_of_priorities', $config['number_of_priorities']);

        $container->setParameter('sulu_audience_targeting.headers.target_group', $config['headers']['target_group']);
        $container->setParameter('sulu_audience_targeting.headers.url', $config['headers']['url']);

        $container->setParameter('sulu_audience_targeting.url', $config['url']);
        $container->setParameter('sulu_audience_targeting.hit.url', $config['hit']['url']);
        $container->setParameter(
            'sulu_audience_targeting.hit.headers.referrer',
            $config['hit']['headers']['referrer']
        );
        $container->setParameter(
            'sulu_audience_targeting.hit.headers.uuid',
            $config['hit']['headers']['uuid']
        );
        $container->setParameter(
            'sulu_audience_targeting.cookies.target_group',
            $config['cookies']['target_group']
        );
        $container->setParameter(
            'sulu_audience_targeting.cookies.session',
            $config['cookies']['session']
        );

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configurePersistence($config['objects'], $container);
    }

    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'target_groups' => [
                            'routes' => [
                                'list' => 'get_target-groups',
                                'detail' => 'get_target-group',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'target_group_selection' => [
                                'default_type' => 'list',
                                'resource_key' => 'target_groups',
                                'types' => [
                                    'list' => [
                                        'adapter' => 'tree_table_slim',
                                        'list_key' => 'target_groups_selection',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }
    }
}
