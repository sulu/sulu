<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManager;
use Sulu\Bundle\WebsiteBundle\Controller\AnalyticsController;
use Sulu\Bundle\WebsiteBundle\Entity\Domain;
use Sulu\Bundle\WebsiteBundle\Entity\DomainRepository;
use Sulu\Bundle\WebsiteBundle\EventListener\AppendAnalyticsListener;
use Sulu\Bundle\WebsiteBundle\EventSubscriber\AnalyticsSerializeEventSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->alias('sulu_website.analytics.repository', 'sulu.repository.analytics');

    $services->set('sulu_website.domains.repository', DomainRepository::class)
        ->args([Domain::class])
        ->factory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);

    $services->set('sulu_website.analytics.manager', AnalyticsManager::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.analytics'),
            new Reference('sulu_website.domains.repository'),
            '%kernel.environment%',
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_website.analytics.response_listener', AppendAnalyticsListener::class)
        ->args([
            new Reference('twig'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu.repository.analytics'),
            '%kernel.environment%',
            expr('container.hasParameter(\'sulu.preview\') ? parameter(\'sulu.preview\') : \'\''),
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onResponse', 'priority' => -5]);

    $services->set('sulu_website.analytics.event_subscriber', AnalyticsSerializeEventSubscriber::class)
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_website.analytics_controller', AnalyticsController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_website.analytics.manager'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_website.http_cache.clearer'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
