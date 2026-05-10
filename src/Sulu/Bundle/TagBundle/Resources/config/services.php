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

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services
        ->set('sulu_tag.admin', \Sulu\Bundle\TagBundle\Admin\TagAdmin::class)
        ->args([
            service(\Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface::class),
            service('sulu_security.security_checker'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services
        ->set('sulu_tag.tag_manager', \Sulu\Bundle\TagBundle\Tag\TagManager::class)
        ->public()
        ->args([
            service('sulu.repository.tag'),
            service('doctrine.orm.entity_manager'),
            service('event_dispatcher'),
            service('sulu_activity.domain_event_collector'),
            service('sulu_trash.trash_manager')->nullOnInvalid(),
        ]);

    $services->alias(\Sulu\Bundle\TagBundle\Tag\TagManagerInterface::class, 'sulu_tag.tag_manager');

    $services
        ->set('sulu_tag.tag_request_handler', \Sulu\Component\Tag\Request\TagRequestHandler::class)
        ->args([service('request_stack')]);

    $services
        ->set('sulu_tag.twig_extension', \Sulu\Bundle\TagBundle\Twig\TagTwigExtension::class)
        ->args([
            service('sulu.repository.tag'),
            service('sulu_tag.tag_request_handler'),
            service('sulu_core.array_serializer'),
            service('sulu_core.cache.memoize'),
        ])
        ->tag('twig.extension');

    $services
        ->set('sulu_tag.tag_controller', \Sulu\Bundle\TagBundle\Controller\TagController::class)
        ->public()
        ->args([
            service('fos_rest.view_handler'),
            service('sulu_core.doctrine_rest_helper'),
            service('sulu_core.list_builder.field_descriptor_factory'),
            service('sulu_core.doctrine_list_builder_factory'),
            service('sulu_tag.tag_manager'),
            service('sulu.repository.tag'),
            service('doctrine.orm.entity_manager'),
            service('router'),
            '%sulu.model.tag.class%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services
        ->set('sulu_tag.create_action', \Sulu\Bundle\TagBundle\Controller\TagCreateAction::class)
        ->public()
        ->args([service('sulu_core.sulu_serializer'), service('sulu_tag.tag_manager')])
        ->tag('sulu.context', ['context' => 'admin']);

    $services
        ->set('sulu_tag.get_action', \Sulu\Bundle\TagBundle\Controller\TagGetAction::class)
        ->public()
        ->args([service('sulu_core.sulu_serializer'), service('sulu_tag.tag_manager')])
        ->tag('sulu_context', ['context' => 'admin']);

    $services
        ->set('sulu_tag.put_action', \Sulu\Bundle\TagBundle\Controller\TagPutAction::class)
        ->public()
        ->args([service('sulu_core.sulu_serializer'), service('sulu_tag.tag_manager')])
        ->tag('sulu_context', ['context' => 'admin']);

    $services
        ->set('sulu_tag.delete_action', \Sulu\Bundle\TagBundle\Controller\TagDeleteAction::class)
        ->public()
        ->args([service('sulu_core.sulu_serializer'), service('sulu_tag.tag_manager')])
        ->tag('sulu_context', ['context' => 'admin']);

    $services
        ->set('sulu_tag.get_all_action', \Sulu\Bundle\TagBundle\Controller\TagGetAllAction::class)
        ->public()
        ->args([
            service('sulu_core.sulu_serializer'),
            service('sulu_core.doctrine_rest_helper'),
            service('sulu_core.list_builder.field_descriptor_factory'),
            service('sulu_core.doctrine_list_builder_factory'),
            service('sulu.repository.tag'),
            '%sulu.model.tag.class%',
        ])
        ->tag('sulu_context', ['context' => 'admin']);
};
