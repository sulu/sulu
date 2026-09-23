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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Container\ContainerInterface;
use Sulu\Content\UserInterface\Controller\Admin\WorkflowTransitionRequestController;
use Sulu\Content\UserInterface\Controller\Website\ContentController;
use Symfony\Component\DependencyInjection\ContainerInterface as DIContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.content_controller', ContentController::class)
        ->public()
        ->tag('container.service_subscriber')
        ->tag('controller.service_arguments')
        ->call('setContainer', [new Reference(ContainerInterface::class)]);

    $services->alias(ContentController::class, 'sulu_content.content_controller')
        ->public();

    $services->set('sulu_content.workflow_transition_request_controller', WorkflowTransitionRequestController::class)
        ->public()
        ->args([
            new Reference('sulu_content.workflow_transition_request_repository'),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu_content.workflow_transition_request_security_context_resolver'),
            new Reference('sulu_content.workflow_transition_request_view_factory'),
            new Reference('sulu_content.workflow_transition_request_status_resolver'),
            new Reference('sulu_message_bus'),
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage', DIContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
