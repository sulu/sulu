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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluContactExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter(
            'sulu_contact.defaults',
            $config['defaults']
        );

        $container->setParameter(
            'sulu_contact.account_types',
            $config['account_types']
        );

        $this->setDefaultForFormOfAddress($config);
        $container->setParameter(
            'sulu_contact.form_of_address',
            $config['form_of_address']
        );
    }

    /**
     * Sets default values for form of address if not defined in config
     * @param $config
     */
    private function setDefaultForFormOfAddress($config)
    {
        if (!array_key_exists('form_of_address', $config) || count($config['form_of_address']) == 0) {
            $config['form_of_address'] = array(
                'male' => array(
                    'id' => 0,
                    'name' => 'male',
                    'translation' => 'contact.contacts.formOfAddress.male'
                ),
                'female' => array(
                    'id' => 1,
                    'name' => 'female',
                    'translation' => 'contact.contacts.formOfAddress.female'
                )
            );
        }
    }
}
