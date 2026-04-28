<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Bundle\PreviewBundle\Admin\PreviewAdmin;
use Sulu\Bundle\PreviewBundle\Application\Manager\PreviewLinkManager;
use Sulu\Bundle\PreviewBundle\Infrastructure\Doctrine\Repository\PreviewLinkRepository;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\EventSubscriber\CacheCommandSubscriber;
use Sulu\Bundle\PreviewBundle\Preview\Events;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRenderer;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\WebsiteKernelFactory;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PreviewController;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PreviewLinkController;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PublicPreviewController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_preview.events.pre-render', Events::PRE_RENDER);

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
        ->args([new Reference('sulu_preview.preview.kernel_factory')])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_preview.preview.kernel_factory', WebsiteKernelFactory::class)
        ->args(['%kernel.environment%']);

    $services->set('sulu_preview.preview.renderer', PreviewRenderer::class)
        ->args([
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
        ->args([new Reference('doctrine.orm.entity_manager')]);

    $services->set('sulu_preview.preview_link_manager', PreviewLinkManager::class)
        ->args([
            new Reference('sulu_preview.preview_link_repository'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu_preview.preview_object_provider_registry'),
            new Reference('router'),
        ]);

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
