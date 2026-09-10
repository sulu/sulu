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

use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Infrastructure\Doctrine\EventListener\CascadeDeleteWorkflowTransitionRequestListener;
use Sulu\Content\Infrastructure\Doctrine\Repository\WorkflowTransitionRequestRepository;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.workflow_transition_request_repository', WorkflowTransitionRequestRepository::class)
        ->args([new Reference('doctrine.orm.entity_manager')])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->alias(WorkflowTransitionRequestRepositoryInterface::class, 'sulu_content.workflow_transition_request_repository');

    $services->set('sulu_content.workflow_transition_request_cascade_delete_listener', CascadeDeleteWorkflowTransitionRequestListener::class)
        ->tag('doctrine.event_listener', ['event' => 'onFlush']);
};
