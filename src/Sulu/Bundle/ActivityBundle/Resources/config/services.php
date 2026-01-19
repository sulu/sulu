<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollector;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcher;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Application\Subscriber\DispatchSpecificDomainEventSubscriber;
use Sulu\Bundle\ActivityBundle\Application\Subscriber\SetDomainEventUserSubscriber;
use Sulu\Bundle\ActivityBundle\Application\Subscriber\StoreActivitySubscriber;
use Sulu\Bundle\ActivityBundle\Domain\Repository\NullActivityRepository;
use Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Repository\ActivityRepository;
use Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Subscriber\DomainEventCollectorSubscriber;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\ActivityAdmin;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactory;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Metadata\ActivitiesListMetadataVisitor;
use Sulu\Bundle\ActivityBundle\UserInterface\Controller\ActivityController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_activity.domain_event_dispatcher', DomainEventDispatcher::class)
        ->args([new Reference('event_dispatcher')]);

    $services->alias(DomainEventDispatcherInterface::class, 'sulu_activity.domain_event_dispatcher');

    $services->set('sulu_activity.domain_event_collector', DomainEventCollector::class)
        ->args([new Reference('sulu_activity.domain_event_dispatcher')]);

    $services->alias(DomainEventCollectorInterface::class, 'sulu_activity.domain_event_collector');

    $services->set('sulu_activity.activity_repository.null', NullActivityRepository::class)
        ->args(['%sulu.model.activity.class%']);

    $services->set('sulu_activity.activity_repository.doctrine', ActivityRepository::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            '%sulu_activity.storage.persist_payload%',
        ]);

    $services->set('sulu_activity.domain_event_collector_subscriber', DomainEventCollectorSubscriber::class)
        ->args([new Reference('sulu_activity.domain_event_collector')])
        ->tag('doctrine.event_listener', ['event' => 'onClear', 'priority' => -256])
        ->tag('doctrine.event_listener', ['event' => 'postFlush', 'priority' => -256]);

    $services->set('sulu_activity.store_activity_subscriber', StoreActivitySubscriber::class)
        ->args([new Reference('sulu_activity.activity_repository')])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_activity.set_domain_event_user_subscriber', SetDomainEventUserSubscriber::class)
        ->args([new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_activity.dispatch_specific_domain_event_subscriber', DispatchSpecificDomainEventSubscriber::class)
        ->args([new Reference('event_dispatcher')])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_activity.activity_controller', ActivityController::class)
        ->public()
        ->args([
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_security.security_checker'),
            new Reference('translator'),
            '%sulu.model.activity.class%',
            '%sulu.model.contact.class%',
            '%sulu.model.user.class%',
            '%sulu_security.permissions%',
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_activity.activity_admin', ActivityAdmin::class)
        ->args([
            new Reference('sulu_admin.view_builder_factory'),
            new Reference('sulu_security.security_checker'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_activity.activity_list_view_builder_factory', ActivityViewBuilderFactory::class)
        ->args([
            new Reference('sulu_admin.view_builder_factory'),
            new Reference('sulu_security.security_checker'),
        ]);

    $services->alias(ActivityViewBuilderFactoryInterface::class, 'sulu_activity.activity_list_view_builder_factory');

    $services->set('sulu_activity.activities_list_metadata_visitor', ActivitiesListMetadataVisitor::class)
        ->tag('sulu_admin.list_metadata_visitor');
};
