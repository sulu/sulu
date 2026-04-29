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

use Sulu\Bundle\CustomUrlBundle\Controller\CustomUrlController;
use Sulu\Bundle\CustomUrlBundle\Controller\CustomUrlRouteController;
use Sulu\Component\CustomUrl\Document\Initializer\CustomUrlInitializer;
use Sulu\Component\CustomUrl\Document\Subscriber\CustomUrlSubscriber;
use Sulu\Component\CustomUrl\Document\Subscriber\InvalidationSubscriber;
use Sulu\Component\CustomUrl\Generator\Generator;
use Sulu\Component\CustomUrl\Manager\CustomUrlManager;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepository;
use Sulu\Component\CustomUrl\WebspaceCustomUrlProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_custom_urls.repository', CustomUrlRepository::class)
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_page.content_repository'),
            new Reference('sulu_custom_urls.domain_generator'),
            new Reference('sulu_security.user_manager'),
        ]);

    $services->set('sulu_custom_urls.manager', CustomUrlManager::class)
        ->public()
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_custom_urls.repository'),
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_document_manager.path_builder'),
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
            new Reference('sulu_document_manager.document_domain_event_collector'),
        ]);

    $services->set('sulu_custom_urls.initializer', CustomUrlInitializer::class)
        ->args([
            new Reference('sulu_document_manager.node_manager'),
            new Reference('sulu_document_manager.path_builder'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu.phpcr.session'),
        ])
        ->tag('sulu_document_manager.initializer', ['priority' => -127]);

    $services->set('sulu_custom_urls.subscriber', CustomUrlSubscriber::class)
        ->args([
            new Reference('sulu_custom_urls.domain_generator'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.path_builder'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_core.webspace.webspace_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_custom_urls.event_subscriber.invalidation', InvalidationSubscriber::class)
        ->args([
            new Reference('sulu_custom_urls.manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('request_stack'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_custom_urls.domain_generator', Generator::class)
        ->args([new Reference('sulu_core.webspace.webspace_manager.url_replacer')]);

    $services->set('sulu_custom_urls.url_provider', WebspaceCustomUrlProvider::class)
        ->args([new Reference('sulu_custom_urls.manager')])
        ->tag('sulu.webspace.url_provider');

    $services->set('sulu_custom_urls.custom_url_controller', CustomUrlController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_custom_urls.manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_custom_urls.custom_url_route_controller', CustomUrlRouteController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_custom_urls.manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
