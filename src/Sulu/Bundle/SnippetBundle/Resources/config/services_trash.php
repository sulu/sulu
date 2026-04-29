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

use Sulu\Bundle\SnippetBundle\EventListener\SnippetTrashSubscriber;
use Sulu\Bundle\SnippetBundle\Trash\SnippetTrashItemHandler;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_snippet.snippet_trash_subscriber', SnippetTrashSubscriber::class)
        ->args([
            new Reference('sulu_trash.trash_manager'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('sulu_document_manager.event_subscriber');

    $services->set('sulu_snippet.snippet_trash_item_handler', SnippetTrashItemHandler::class)
        ->args([
            new Reference('sulu_trash.trash_item_repository'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.document_domain_event_collector'),
        ])
        ->tag('sulu_trash.store_trash_item_handler')
        ->tag('sulu_trash.restore_trash_item_handler')
        ->tag('sulu_trash.restore_configuration_provider');
};
