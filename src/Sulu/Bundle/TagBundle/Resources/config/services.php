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
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Content\Types\TagSelection;
use Sulu\Bundle\TagBundle\Controller\TagController;
use Sulu\Bundle\TagBundle\Search\TagsConverter;
use Sulu\Bundle\TagBundle\Tag\TagManager;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Bundle\TagBundle\Twig\TagTwigExtension;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Tag\Request\TagRequestHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_tag.admin', TagAdmin::class)
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_security.security_checker'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_tag.tag_manager', TagManager::class)
        ->public()
        ->args([
            new Reference('sulu.repository.tag'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('event_dispatcher'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->alias(TagManagerInterface::class, 'sulu_tag.tag_manager');

    $services->set('sulu_tag.content.type.tag_selection', TagSelection::class)
        ->args([new Reference('sulu_tag.tag_manager')])
        ->tag('sulu.content.type', ['alias' => 'tag_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false]);

    $services->set('sulu_tag.tag_request_handler', TagRequestHandler::class)
        ->args([new Reference('request_stack')]);

    $services->set('sulu_tag.twig_extension', TagTwigExtension::class)
        ->args([
            new Reference('sulu_tag.tag_manager'),
            new Reference('sulu_tag.tag_request_handler'),
            new Reference('sulu_core.array_serializer'),
            new Reference('sulu_core.cache.memoize'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_tag.search.tags_converter', TagsConverter::class)
        ->args([new Reference('sulu_tag.tag_manager')])
        ->tag('massive_search.converter', ['from' => 'tags']);

    $services->set('sulu_tag.reference_store.tag', ReferenceStore::class)
        ->tag('sulu_website.reference_store', ['alias' => 'tag'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_tag.tag_controller', TagController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_tag.tag_manager'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('router'),
            '%sulu.model.tag.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
