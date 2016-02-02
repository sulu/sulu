<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DependencyInjection;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluContactExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_search',
                [
                    'indexes' => [
                        'contact' => [
                            'security_context' => 'sulu.contact.people',
                        ],
                        'account' => [
                            'security_context' => 'sulu.contact.organizations',
                        ],
                    ],
                ]
            );
        }

        if ($container->hasExtension('sulu_media')) {
            $container->prependExtensionConfig(
                'sulu_media',
                [
                    'system_collections' => [
                        'sulu_contact' => [
                            'meta_title' => ['en' => 'Sulu contacts', 'de' => 'Sulu Kontakte'],
                            'collections' => [
                                'contact' => [
                                    'meta_title' => ['en' => 'People', 'de' => 'Personen'],
                                ],
                                'account' => [
                                    'meta_title' => ['en' => 'Organizations', 'de' => 'Organisationen'],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('content.xml');

        $container->setParameter(
            'sulu_contact.defaults',
            $config['defaults']
        );

        $this->setDefaultForFormOfAddress($config);
        $container->setParameter(
            'sulu_contact.form_of_address',
            $config['form_of_address']
        );
        $container->setParameter(
            'sulu_contact.contact_form.category_root',
            $config['form']['contact']['category_root']
        );
        $container->setParameter(
            'sulu_contact.account_form.category_root',
            $config['form']['account']['category_root']
        );
        $container->setParameter(
            'sulu_contact.content-type.contact.template',
            $config['types']['contact']['template']
        );

        $this->configurePersistence($config['objects'], $container);
    }

    /**
     * Sets default values for form of address if not defined in config.
     *
     * @param $config
     */
    private function setDefaultForFormOfAddress($config)
    {
        if (!array_key_exists('form_of_address', $config) || count($config['form_of_address']) == 0) {
            $config['form_of_address'] = [
                'male' => [
                    'id' => 0,
                    'name' => 'male',
                    'translation' => 'contact.contacts.formOfAddress.male',
                ],
                'female' => [
                    'id' => 1,
                    'name' => 'female',
                    'translation' => 'contact.contacts.formOfAddress.female',
                ],
            ];
        }
    }
}
