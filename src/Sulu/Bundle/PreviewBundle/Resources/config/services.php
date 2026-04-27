<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\PreviewBundle\Admin\PreviewAdmin;
use Sulu\Bundle\PreviewBundle\Application\Manager\PreviewLinkManager;
use Sulu\Bundle\PreviewBundle\Infrastructure\Doctrine\Repository\PreviewLinkRepository;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\EventSubscriber\CacheCommandSubscriber;
use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRenderer;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\WebsiteKernelFactory;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PreviewController;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PreviewLinkController;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PublicPreviewController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $parameters = $container->parameters();
    $parameters->set('sulu_preview.events.pre-render', Events::PRE_RENDER);

    $services = $container->services();
    $services->set('sulu_preview.admin', PreviewAdmin::class)
        ->args([
            new Reference('router'),
            '%sulu_preview.delay%',
            '%sulu_preview.mode%',
            '%kernel.bundles%',
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_preview.cache_command_subscriber', CacheCommandSubscriber::class)
        ->args([
            new Reference('sulu_preview.preview.kernel_factory'),
            '%kernel.environment%',
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    // Preview
    $services->set('sulu_preview.preview.kernel_factory', WebsiteKernelFactory::class)
        ->args(['%kernel.environment%']);

    $services->set('sulu_preview.preview.renderer', PreviewRenderer::class)
        ->args([
            new Reference('sulu_route.routing.defaults_provider'),
            new Reference('request_stack'),
            new Reference('sulu_preview.preview.kernel_factory'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('event_dispatcher'),
            [],
            '%kernel.environment%',
            expr('
                container.hasParameter(\'sulu_audience_targeting.headers.target_group\')
                    ? parameter(\'sulu_audience_targeting.headers.target_group\')
                    : null
            '),
        ]);

    $services->set('sulu_preview.preview', Preview::class)
        ->args([
            new Reference('sulu_preview.preview_object_provider_registry'),
            new Reference('sulu_preview.preview.cache'),
            new Reference('sulu_preview.preview.renderer'),
        ]);

    $services->set('sulu_preview.preview_object_provider_registry', PreviewObjectProviderRegistry::class)
        ->args([tagged_iterator('sulu_preview.object_provider', indexAttribute: 'provider-key')]);

    $services->set('sulu_preview.preview_link_repository', PreviewLinkRepository::class)
        ->args([new Reference(EntityManagerInterface::class)]);

    $services->set('sulu_preview.preview_link_manager', PreviewLinkManager::class)
        ->args([
            new Reference('sulu_preview.preview_link_repository'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu_preview.preview_object_provider_registry'),
            new Reference('router'),
        ]);

    // Controller
    $services->set('sulu_preview.preview_controller', PreviewController::class)
        ->public()
        ->args([
            new Reference('sulu_preview.preview'),
            new Reference('security.token_storage'),
            new Reference('profiler', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_preview.public_preview_controller', PublicPreviewController::class)
        ->public()
        ->args([
            new Reference('sulu_preview.preview.renderer'),
            new Reference('sulu_preview.preview_object_provider_registry'),
            new Reference('sulu_preview.preview_link_repository'),
            new Reference('twig'),
            new Reference('profiler', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_preview.preview_link_controller', PreviewLinkController::class)
        ->public()
        ->args([
            new Reference('sulu_preview.preview_link_repository'),
            new Reference('sulu_preview.preview_link_manager'),
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
