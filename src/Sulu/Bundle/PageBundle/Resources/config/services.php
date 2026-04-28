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

use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Build\NodeOrderBuilder;
use Sulu\Bundle\PageBundle\Content\Structure\ExcerptStructureExtension;
use Sulu\Bundle\PageBundle\Content\Structure\SeoStructureExtension;
use Sulu\Bundle\PageBundle\Controller\PageController;
use Sulu\Bundle\PageBundle\Controller\PageResourcelocatorController;
use Sulu\Bundle\PageBundle\Controller\ResourcelocatorController;
use Sulu\Bundle\PageBundle\Controller\WebspaceController;
use Sulu\Bundle\PageBundle\Controller\WebspaceLocalizationController;
use Sulu\Bundle\PageBundle\EventListener\DomainEventSubscriber;
use Sulu\Bundle\PageBundle\EventListener\PageRemoveSubscriber;
use Sulu\Bundle\PageBundle\EventListener\WebspaceSerializeEventSubscriber;
use Sulu\Bundle\PageBundle\Preview\PageObjectProvider;
use Sulu\Bundle\PageBundle\Preview\PageRouteDefaultsProvider;
use Sulu\Bundle\PageBundle\Repository\NodeRepository;
use Sulu\Bundle\PageBundle\Repository\ResourceLocatorRepository;
use Sulu\Bundle\PageBundle\Search\EventListener\PermissionListener;
use Sulu\Bundle\PageBundle\Sitemap\PagesSitemapProvider;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Content\Repository\ContentRepository;
use Sulu\Component\Content\Repository\Serializer\SerializerEventListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_page.admin.class', PageAdmin::class);
    $parameters->set('sulu_page.node_repository.class', NodeRepository::class);
    $parameters->set('sulu_page.rl_repository.class', ResourceLocatorRepository::class);
    $parameters->set('sulu_page.extension.seo.class', SeoStructureExtension::class);
    $parameters->set('sulu_page.extension.excerpt.class', ExcerptStructureExtension::class);

    $services->set('sulu_page.admin', '%sulu_page.admin.class%')
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_page.teaser.provider_pool'),
            '%sulu_document_manager.versioning.enabled%',
            new Reference('sulu_activity.activity_list_view_builder_factory'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.permission_listener', PermissionListener::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('massive_search.search_manager'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_page.webspace.serializer.event_subscriber', WebspaceSerializeEventSubscriber::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_core.webspace.url_provider'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%kernel.environment%',
        ])
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.node_repository', '%sulu_page.node_repository.class%')
        ->public()
        ->args([
            new Reference('sulu.content.mapper'),
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_security.user_manager'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_page.smart_content.data_provider.content.query_builder'),
            new Reference('sulu.content.query_executor'),
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_page.rl_repository', '%sulu_page.rl_repository.class%')
        ->public()
        ->args([
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('sulu.content.structure_manager'),
        ]);

    $services->set('sulu_page.resource_locator_controller', ResourcelocatorController::class)
        ->public()
        ->args([
            new Reference('sulu.content.resource_locator.strategy_pool'),
            new Reference('fos_rest.view_handler'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.page_controller', PageController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu.content.mapper'),
            new Reference('sulu_page.content_repository'),
            new Reference('sulu_hash.request_hash_checker'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_page.node_repository'),
            new Reference('sulu_document_manager.metadata_factory.base'),
            new Reference('form.factory'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.page_resource_locator_controller', PageResourcelocatorController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_page.rl_repository'),
            new Reference('sulu_document_manager.document_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.webspace_controller', WebspaceController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_security.security_checker'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.webspace_localization_controller', WebspaceLocalizationController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.webspace.webspace_manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.extension.seo', '%sulu_page.extension.seo.class%')
        ->tag('sulu.structure.extension');

    $services->set('sulu_page.extension.excerpt', '%sulu_page.extension.excerpt.class%')
        ->args([
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu.content.type_manager'),
            new Reference('sulu_page.export.manager'),
            new Reference('sulu_page.import.manager'),
            new Reference('massive_search.factory'),
        ])
        ->tag('sulu.structure.extension');

    $services->set('sulu_page.content_repository', ContentRepository::class)
        ->public()
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.property_encoder'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu.content.localization_finder'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu.util.node_helper'),
            new Reference('sulu_security.system_store'),
            '%sulu_security.permissions%',
        ])
        ->tag('sulu_security.access_control_descendant_provider');

    $services->set('sulu_page.content_repository.event_subscriber', SerializerEventListener::class)
        ->args([
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.preview.object_provider', PageObjectProvider::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('jms_serializer'),
            new Reference('sulu_document_manager.document_inspector'),
        ])
        ->tag('sulu_preview.object_provider', ['provider_key' => 'pages', 'provider-key' => 'pages']);

    $services->set('sulu_page.preview.defaults_provider', PageRouteDefaultsProvider::class)
        ->args([
            new Reference('sulu_page.structure.factory'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu.content.structure_manager'),
        ])
        ->tag('sulu_route.defaults_provider');

    $services->set('sulu_core.build.builder.node_order', NodeOrderBuilder::class)
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_document_manager.live_session'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_document_manager.property_encoder'),
        ])
        ->tag('massive_build.builder');

    $services->set('sulu_page.reference_store.content', ReferenceStore::class)
        ->tag('sulu_website.reference_store', ['alias' => 'pages'])
        ->tag('sulu_website.reference_store', ['alias' => 'content'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_page.pages_sitemap_provider', PagesSitemapProvider::class)
        ->args([
            new Reference('sulu_page.content_repository'),
            new Reference('sulu_core.webspace.webspace_manager'),
            '%kernel.environment%',
            new Reference('sulu_security.access_control_manager'),
        ])
        ->tag('sulu.sitemap.provider');

    $services->set('sulu_page.page_remove_subscriber', PageRemoveSubscriber::class)
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu.repository.access_control'),
            new Reference('sulu_security.system_store'),
            new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_security.permissions%',
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_page.document_manager.event_subscriber', DomainEventSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_domain_event_collector'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.property_encoder'),
        ])
        ->tag('sulu_document_manager.event_subscriber');
};
