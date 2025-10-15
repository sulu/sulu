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
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Trash\CustomUrlTrashItemHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function(ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('sulu_custom_urls.custom_url_trash_item_handler', CustomUrlTrashItemHandler::class)
        ->args([
            service(CustomUrlRepositoryInterface::class),
            tagged_iterator(CustomUrlMapperInterface::SERVICE_TAG),
            service(TrashItemRepositoryInterface::class),
            service(DomainEventCollectorInterface::class),
            service(EntityManagerInterface::class),
        ])
        ->tag('sulu_trash.store_trash_item_handler')
        ->tag('sulu_trash.restore_trash_item_handler')
        ->tag('sulu_trash.restore_configuration_provider')
    ;
    $services->alias(CustomUrlTrashItemHandler::class, 'sulu_custom_urls.custom_url_trash_item_handler');
};
