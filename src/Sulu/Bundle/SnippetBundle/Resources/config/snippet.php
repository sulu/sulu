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

use Sulu\Bundle\SnippetBundle\Controller\LanguageController;
use Sulu\Bundle\SnippetBundle\Controller\SnippetAreaController;
use Sulu\Bundle\SnippetBundle\Controller\SnippetController;
use Sulu\Bundle\SnippetBundle\Document\SnippetInitializer;
use Sulu\Bundle\SnippetBundle\EventListener\CacheInvalidationSubscriber;
use Sulu\Bundle\SnippetBundle\EventListener\DomainEventSubscriber;
use Sulu\Bundle\SnippetBundle\Form\SnippetType;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Bundle\SnippetBundle\Twig\DefaultSnippetTwigExtension;
use Sulu\Bundle\SnippetBundle\Twig\MemoizedSnippetTwigExtension;
use Sulu\Bundle\SnippetBundle\Twig\SnippetAreaTwigExtension;
use Sulu\Bundle\SnippetBundle\Twig\SnippetTwigExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_snippet.repository.class', SnippetRepository::class);
    $parameters->set('sulu_snippet.controller.snippet.class', SnippetController::class);
    $parameters->set('sulu_snippet.twig.snippet.class', SnippetTwigExtension::class);
    $parameters->set('sulu_snippet.twig.snippet.memoized.class', MemoizedSnippetTwigExtension::class);
    $parameters->set('sulu_snippet.form.snippet.class', SnippetType::class);
    $parameters->set('sulu_snippet.document.snippet_initializer.class', SnippetInitializer::class);

    $services->set('sulu_snippet.snippet_area_controller', SnippetAreaController::class)
        ->public()
        ->args([
            new Reference('sulu_snippet.default_snippet.manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_security.security_checker'),
            new Reference('security.token_storage'),
            '%sulu_snippet.areas%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_snippet.language_controller', LanguageController::class)
        ->public()
        ->args([new Reference('sulu_core.webspace.webspace_manager')])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_snippet.repository', '%sulu_snippet.repository.class%')
        ->public()
        ->args([
            new Reference('sulu.phpcr.session'),
            new Reference('sulu.content.mapper'),
            new Reference('sulu_document_manager.document_manager'),
        ]);

    $services->set('sulu_snippet.controller.snippet', '%sulu_snippet.controller.snippet.class%')
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu.content.mapper'),
            new Reference('sulu.content.structure_manager'),
            new Reference('sulu_snippet.repository'),
            new Reference('security.token_storage', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            new Reference('router'),
            new Reference('sulu_snippet.default_snippet.manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('form.factory'),
            new Reference('sulu_hash.request_hash_checker'),
            new Reference('sulu_core.list_rest_helper'),
            new Reference('sulu_document_manager.metadata_factory.base'),
            new Reference('translator'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_snippet.twig.snippet', '%sulu_snippet.twig.snippet.class%')
        ->args([
            new Reference('sulu.content.mapper'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_website.resolver.structure'),
        ]);

    $services->set('sulu_snippet.twig.snippet.memoized', '%sulu_snippet.twig.snippet.memoized.class%')
        ->args([
            new Reference('sulu_snippet.twig.snippet'),
            new Reference('sulu_core.cache.memoize'),
            '%sulu_snippet.twig.snippet.cache_lifetime%',
        ])
        ->tag('twig.extension');

    $services->set('sulu_snippet.twig.default_snippet', DefaultSnippetTwigExtension::class)
        ->args([
            new Reference('sulu_snippet.default_snippet.manager'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_snippet.resolver'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_snippet.twig.area_snippet', SnippetAreaTwigExtension::class)
        ->args([
            new Reference('sulu_snippet.default_snippet.manager'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_snippet.resolver'),
            new Reference('sulu_snippet.reference_store.snippet_area'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_snippet.form.snippet', '%sulu_snippet.form.snippet.class%')
        ->tag('form.type');

    $services->set('sulu_snippet.document.snippet_initializer', '%sulu_snippet.document.snippet_initializer.class%')
        ->args([
            new Reference('doctrine_phpcr'),
            new Reference('sulu_document_manager.path_builder'),
        ])
        ->tag('sulu_document_manager.initializer');

    $services->set('sulu_snippet.cache_invalidation_subscriber', CacheInvalidationSubscriber::class)
        ->args([
            new Reference('sulu_snippet.default_snippet.manager'),
            new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%sulu_snippet.areas%',
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_snippet.document_manager.event_subscriber', DomainEventSubscriber::class)
        ->args([
            new Reference('sulu_document_manager.document_domain_event_collector'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.property_encoder'),
        ])
        ->tag('sulu_document_manager.event_subscriber');
};
