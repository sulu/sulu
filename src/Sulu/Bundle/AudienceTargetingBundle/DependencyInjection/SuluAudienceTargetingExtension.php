<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\DependencyInjection;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Container extension for sulu audience targeting.
 */
class SuluAudienceTargetingExtension extends Extension
{
    use PersistenceExtensionTrait;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sulu_audience_targeting.number_of_priorities', $config['number_of_priorities']);
        $container->setParameter('sulu_audience_targeting.frequencies', [
            TargetGroupRuleInterface::FREQUENCY_HIT => TargetGroupRuleInterface::FREQUENCY_HIT_NAME,
            TargetGroupRuleInterface::FREQUENCY_SESSION => TargetGroupRuleInterface::FREQUENCY_SESSION_NAME,
            TargetGroupRuleInterface::FREQUENCY_VISITOR => TargetGroupRuleInterface::FREQUENCY_VISITOR_NAME,
        ]);

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
}
