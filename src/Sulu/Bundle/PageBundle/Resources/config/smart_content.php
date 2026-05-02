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

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use Sulu\Bundle\PageBundle\Controller\SmartContentItemController;
use Sulu\Component\Content\SmartContent\PageDataProvider;
use Sulu\Component\Content\SmartContent\QueryBuilder;
use Sulu\Component\SmartContent\ContentType;
use Sulu\Component\SmartContent\DataProviderPool;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_page.smart_content.data_provider_pool.class', DataProviderPool::class);
    $parameters->set('sulu_page.smart_content.data_provider.content.query_builder.class', QueryBuilder::class);
    $parameters->set('sulu_page.smart_content.data_provider.page.class', PageDataProvider::class);
    $parameters->set('sulu_page.smart_content.data_provider.content.proxy_factory.class', LazyLoadingValueHolderFactory::class);
    $parameters->set('sulu_page.smart_content.content_type.class', ContentType::class);

    $services->set('sulu_page.smart_content.data_provider_pool', '%sulu_page.smart_content.data_provider_pool.class%')
        ->public();

    $services->set('sulu_page.smart_content.data_provider.content.proxy_factory', '%sulu_page.smart_content.data_provider.content.proxy_factory.class%')
        ->args([new Reference('sulu_core.proxy_manager.configuration')]);

    $services->set('sulu_page.smart_content.data_provider.content.query_builder', '%sulu_page.smart_content.data_provider.content.query_builder.class%')
        ->private()
        ->args([
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            new Reference('sulu.phpcr.session'),
            '%sulu.content.language.namespace%',
        ]);

    $services->set('sulu_page.smart_content.data_provider.content', '%sulu_page.smart_content.data_provider.page.class%')
        ->args([
            new Reference('sulu_page.smart_content.data_provider.content.query_builder'),
            new Reference('sulu.content.query_executor'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_page.smart_content.data_provider.content.proxy_factory'),
            new Reference('sulu_document_manager.default_session'),
            new Reference('sulu_page.reference_store.content'),
            '%sulu_document_manager.show_drafts%',
            '%sulu_security.permissions%',
            expr('container.hasParameter(\'sulu_audience_targeting.enabled\')'),
            new Reference('sulu_admin.form_metadata_provider', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_website.enabled_twig_attributes%',
        ])
        ->tag('sulu.smart_content.data_provider', ['alias' => 'pages']);

    $services->set('sulu_page.smart_content.content_type', '%sulu_page.smart_content.content_type.class%')
        ->args([
            new Reference('sulu_page.smart_content.data_provider_pool'),
            new Reference('sulu_tag.tag_manager'),
            new Reference('request_stack'),
            new Reference('sulu_tag.tag_request_handler'),
            new Reference('sulu_category.category_request_handler'),
            new Reference('sulu_tag.reference_store.tag'),
            new Reference('sulu_category.reference_store.category'),
            new Reference('sulu_audience_targeting.target_group_store', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('sulu_core.webspace.request_analyzer'),
        ])
        ->tag('sulu.content.type', ['alias' => 'smart_content'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_page.smart_content_item_controller', SmartContentItemController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_tag.tag_manager'),
            new Reference('sulu_page.smart_content.data_provider_pool'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
