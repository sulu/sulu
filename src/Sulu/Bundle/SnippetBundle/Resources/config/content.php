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

use Sulu\Bundle\SnippetBundle\Content\SingleSnippetSelection;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Bundle\SnippetBundle\Content\SnippetDataProvider;
use Sulu\Bundle\SnippetBundle\Content\SnippetQueryBuilder;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManager;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolver;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_snippet.reference_store.snippet', ReferenceStore::class)
        ->public()
        ->tag('sulu_website.reference_store', ['alias' => 'snippet'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_snippet.reference_store.snippet_area', ReferenceStore::class)
        ->public()
        ->tag('sulu_website.reference_store', ['alias' => 'snippet_area'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_snippet.default_snippet.manager', DefaultSnippetManager::class)
        ->public()
        ->args([
            new Reference('sulu_core.webspace.settings_manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_document_manager.document_registry'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu_snippet.areas%',
        ]);

    $services->alias(DefaultSnippetManagerInterface::class, 'sulu_snippet.default_snippet.manager');

    $services->set('sulu_snippet.resolver', SnippetResolver::class)
        ->public()
        ->args([
            new Reference('sulu.content.mapper'),
            new Reference('sulu_website.resolver.structure'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_snippet.content.single_snippet_selection', SingleSnippetSelection::class)
        ->args([
            new Reference('sulu_snippet.resolver'),
            new Reference('sulu_snippet.default_snippet.manager'),
            new Reference('sulu_snippet.reference_store.snippet'),
            new Reference('sulu_snippet.reference_store.snippet_area'),
        ])
        ->tag('sulu.content.type', ['alias' => 'single_snippet_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_snippet.content.snippet', SnippetContent::class)
        ->args([
            new Reference('sulu_snippet.default_snippet.manager'),
            new Reference('sulu_snippet.resolver'),
            new Reference('sulu_snippet.reference_store.snippet'),
            '%sulu_snippet.content-type.default_enabled%',
            new Reference('sulu_snippet.reference_store.snippet_area'),
        ])
        ->tag('sulu.content.type', ['alias' => 'snippet_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_snippet.smart_content.data_provider.query_builder', SnippetQueryBuilder::class)
        ->private()
        ->args([
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_page.extension.manager'),
            new Reference('sulu.phpcr.session'),
            '%sulu.content.language.namespace%',
        ]);

    $services->set('sulu_snippet.smart_content.snippet_data_provider', SnippetDataProvider::class)
        ->args([
            new Reference('sulu.content.query_executor'),
            new Reference('sulu_snippet.smart_content.data_provider.query_builder'),
            new Reference('sulu.util.node_helper'),
            new Reference('sulu_page.smart_content.data_provider.content.proxy_factory'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_snippet.reference_store.snippet'),
            expr('container.hasParameter(\'sulu_audience_targeting.enabled\')'),
            new Reference('sulu_admin.form_metadata_provider', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.smart_content.data_provider', ['alias' => 'snippets']);
};
