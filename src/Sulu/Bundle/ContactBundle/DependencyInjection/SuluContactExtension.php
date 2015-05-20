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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
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

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

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
    }

    /**
     * Sets default values for form of address if not defined in config.
     *
     * @param $config
     */
    private function setDefaultForFormOfAddress($config)
    {
        if (!array_key_exists('form_of_address', $config) || count($config['form_of_address']) == 0) {
            $config['form_of_address'] = array(
                'male' => array(
                    'id' => 0,
                    'name' => 'male',
                    'translation' => 'contact.contacts.formOfAddress.male',
                ),
                'female' => array(
                    'id' => 1,
                    'name' => 'female',
                    'translation' => 'contact.contacts.formOfAddress.female',
                ),
            );
        }
    }
}
