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

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapper;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Application\MessageHandler\CreateCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\MessageHandler\ModifyCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlMessageHandler;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(CustomUrlMapperInterface::class, CustomUrlMapper::class);

    $services->set(CreateCustomUrlMessageHandler::class)
        ->args([
            tagged_iterator('sulu_snippet.snippet_mapper'),
            new Reference(CustomUrlRepositoryInterface::class),
            new Reference(DomainEventCollectorInterface::class),
        ])
        ->tag('messenger.message_handler')
    ;

    $services->set(ModifyCustomUrlMessageHandler::class)
        ->args([
            tagged_iterator('sulu_snippet.snippet_mapper'),
            new Reference(CustomUrlRepositoryInterface::class),
            new Reference(DomainEventCollectorInterface::class),
        ])
        ->tag('messenger.message_handler')
    ;

    $services->set(RemoveCustomUrlMessageHandler::class)
        ->args([
            tagged_iterator('sulu_snippet.snippet_mapper'),
            new Reference(DomainEventCollectorInterface::class),
        ])
        ->tag('messenger.message_handler')
    ;
};
