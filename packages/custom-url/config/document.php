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
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepository;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRouteRepository;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRouteRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\HttpCache\CacheInvalidationSubscriber;
use Sulu\CustomUrl\UserInterface\Controller\Admin\CustomUrlController;
use Sulu\CustomUrl\UserInterface\Controller\Admin\CustomUrlRouteController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

return static function(ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(CustomUrlRepositoryInterface::class, CustomUrlRepository::class)
        ->args([
            new Reference('doctrine'),
            new Reference(CustomUrlRouteRepositoryInterface::class),
        ])
    ;

    $services->alias('sulu_custom_urls.repository', CustomUrlRepositoryInterface::class);

    $services->set(CustomUrlRouteRepositoryInterface::class, CustomUrlRouteRepository::class)
        ->args([
            new Reference(EntityManagerInterface::class),
        ])
    ;

    $services->set('sulu_custom_urls.event_subscriber.invalidation', CacheInvalidationSubscriber::class)
        ->args([
            new Reference(CustomUrlRepositoryInterface::class),
            new Reference(CustomUrlRouteRepositoryInterface::class),
            service('sulu_http_cache.cache_manager')->nullOnInvalid(),
            new Reference('request_stack'),
        ])
        ->tag('sulu_document_manager.event_subscriber')
    ;

    $services->set('sulu_custom_urls.custom_url_controller', CustomUrlController::class)
        ->public()
        ->args([
            new Reference(MessageBusInterface::class),
            new Reference(RequestStack::class),
            new Reference(CustomUrlRepositoryInterface::class),
            new Reference(NormalizerInterface::class),
            new Reference(FieldDescriptorFactoryInterface::class),
            new Reference(DoctrineListBuilderFactoryInterface::class),
            new Reference('sulu_core.doctrine_rest_helper'),
        ])
    ;

    $services->set('sulu_custom_urls.custom_url_route_controller', CustomUrlRouteController::class)
        ->public()
        ->args([
            new Reference('request_stack'),
            new Reference(CustomUrlRouteRepositoryInterface::class),
            new Reference(NormalizerInterface::class),
        ])
    ;
};
