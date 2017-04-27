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
            TargetGroupRuleInterface::FREQUENCY_HIT_NAME => TargetGroupRuleInterface::FREQUENCY_HIT,
            TargetGroupRuleInterface::FREQUENCY_SESSION_NAME => TargetGroupRuleInterface::FREQUENCY_SESSION,
        ]);

        $this->processUserContext($config['user_context'], $container);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->configurePersistence($config['objects'], $container);
    }

    private function processUserContext($userContextConfig, ContainerBuilder $container)
    {
        $container->setParameter('sulu_audience_targeting.user_context.header', $userContextConfig['header']);
        $container->setParameter('sulu_audience_targeting.user_context.url', $userContextConfig['url']);
        $container->setParameter('sulu_audience_targeting.user_context.hit.url', $userContextConfig['hit']['url']);
        $container->setParameter(
            'sulu_audience_targeting.user_context.hit.headers.url',
            $userContextConfig['hit']['headers']['url']
        );
        $container->setParameter(
            'sulu_audience_targeting.user_context.hit.headers.referer',
            $userContextConfig['hit']['headers']['referer']
       );
        $container->setParameter('sulu_audience_targeting.user_context.cookie', $userContextConfig['cookie']);
    }
}
