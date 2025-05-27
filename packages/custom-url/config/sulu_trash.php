<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Trash\CustomUrlTrashItemHandler;
use Sulu\CustomUrl\Infrastructure\Sulu\Trash\CustomUrlTrashSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function(ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('sulu_custom_urls.custom_url_trash_subscriber', CustomUrlTrashSubscriber::class)
        ->args([
            service('sulu_trash.trash_manager'),
            service(EntityManagerInterface::class),
        ])
        ->tag('sulu_document_manager.event_subscriber')
    ;
    $services->alias(CustomUrlTrashSubscriber::class, 'sulu_custom_urls.custom_url_trash_subscriber');

    $services->set('sulu_custom_urls.custom_url_trash_item_handler', CustomUrlTrashItemHandler::class)
        ->args([
            service(CustomUrlRepositoryInterface::class),
            service(CustomUrlMapperInterface::class),
            service(TrashItemRepositoryInterface::class),
            service(DocumentDomainEventCollectorInterface::class),
            service(EntityManagerInterface::class),
        ])
        ->tag('sulu_trash.store_trash_item_handler')
        ->tag('sulu_trash.restore_trash_item_handler')
        ->tag('sulu_trash.restore_configuration_provider')
    ;
    $services->alias(CustomUrlTrashItemHandler::class, 'sulu_custom_urls.custom_url_trash_item_handler');
};
