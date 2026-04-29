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
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Category\CategoryManager;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Category\KeywordManager;
use Sulu\Bundle\CategoryBundle\Controller\CategoryController;
use Sulu\Bundle\CategoryBundle\Controller\KeywordController;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryCreatedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryModifiedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRemovedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRestoredEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryTranslationAddedEvent;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Search\AdminCategoryIndexListener;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Search\AdminCategoryReindexProvider;
use Sulu\Bundle\CategoryBundle\Twig\CategoryTwigExtension;
use Sulu\Component\Category\Request\CategoryRequestHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_category.admin', CategoryAdmin::class)
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu.core.localization_manager'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_category.category_manager', CategoryManager::class)
        ->public()
        ->args([
            new Reference('sulu.repository.category'),
            new Reference('sulu.repository.category_translation'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_category.keyword_manager'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('event_dispatcher'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->alias(CategoryManagerInterface::class, 'sulu_category.category_manager');

    $services->set('sulu_category.category_request_handler', CategoryRequestHandler::class)
        ->args([new Reference('request_stack')]);

    $services->set('sulu_category.twig_extension', CategoryTwigExtension::class)
        ->args([
            new Reference('sulu_category.category_manager'),
            new Reference('sulu_category.category_request_handler'),
            new Reference('sulu_core.array_serializer'),
            new Reference('sulu_core.cache.memoize'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_category.keyword_manager', KeywordManager::class)
        ->public()
        ->args([
            new Reference('sulu.repository.keyword'),
            new Reference('sulu.repository.category_translation'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
        ]);

    $services->set('sulu_category.category_controller', CategoryController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu.repository.category'),
            new Reference('translator'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_category.category_manager'),
            '%sulu.model.category.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_category.keyword_controller', KeywordController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_category.keyword_manager'),
            new Reference('sulu.repository.keyword'),
            new Reference('sulu.repository.category'),
            new Reference('doctrine.orm.entity_manager'),
            '%sulu.model.keyword.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_category.admin_category_index_listener', AdminCategoryIndexListener::class)
        ->args([new Reference('sulu_message_bus')])
        ->tag('kernel.event_listener', ['event' => CategoryCreatedEvent::class, 'method' => 'onCategoryChanged'])
        ->tag('kernel.event_listener', ['event' => CategoryModifiedEvent::class, 'method' => 'onCategoryChanged'])
        ->tag('kernel.event_listener', ['event' => CategoryRemovedEvent::class, 'method' => 'onCategoryChanged'])
        ->tag('kernel.event_listener', ['event' => CategoryTranslationAddedEvent::class, 'method' => 'onCategoryChanged'])
        ->tag('kernel.event_listener', ['event' => CategoryRestoredEvent::class, 'method' => 'onCategoryChanged']);

    $services->set('sulu_category.admin_category_reindex_provider', AdminCategoryReindexProvider::class)
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            tagged_iterator('sulu_category.admin_category_reindex_provider_enhancer'),
        ])
        ->tag('cmsig_seal.reindex_provider');
};
