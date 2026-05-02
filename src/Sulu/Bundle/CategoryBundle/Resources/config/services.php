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
use Sulu\Bundle\CategoryBundle\Content\Types\CategorySelection;
use Sulu\Bundle\CategoryBundle\Content\Types\SingleCategorySelection;
use Sulu\Bundle\CategoryBundle\Controller\CategoryController;
use Sulu\Bundle\CategoryBundle\Controller\KeywordController;
use Sulu\Bundle\CategoryBundle\Search\Converter\CategoryConverter;
use Sulu\Bundle\CategoryBundle\Twig\CategoryTwigExtension;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
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
            new Reference('sulu.repository.category_meta'),
            new Reference('sulu.repository.category_translation'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_category.keyword_manager'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('event_dispatcher'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->alias(CategoryManagerInterface::class, 'sulu_category.category_manager');

    $services->set('sulu_category.content.type.category_selection', CategorySelection::class)
        ->args([new Reference('sulu_category.category_manager')])
        ->tag('sulu.content.type', ['alias' => 'category_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_category.content.type.single_category_selection', SingleCategorySelection::class)
        ->args([new Reference('sulu_category.category_manager')])
        ->tag('sulu.content.type', ['alias' => 'single_category_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

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

    $services->set('sulu_category.reference_store.category', ReferenceStore::class)
        ->tag('sulu_website.reference_store', ['alias' => 'category'])
        ->tag('kernel.reset', ['method' => 'reset']);

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

    $services->set(CategoryConverter::class)
        ->args([
            new Reference('sulu_category.category_manager'),
            new Reference('massive_search.search_manager'),
            new Reference('massive_search.object_to_document_converter'),
            new Reference('event_dispatcher'),
        ])
        ->tag('massive_search.converter', ['from' => 'category']);
};
