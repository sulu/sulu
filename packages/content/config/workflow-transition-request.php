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

use Sulu\Content\Application\ContentWorkflow\Subscriber\RequestWorkflowPreValidationSubscriber;
use Sulu\Content\Application\RequestWorkflow\PreValidator\ExcerptRequiredPreValidator;
use Sulu\Content\Application\RequestWorkflow\PreValidator\SeoRequiredPreValidator;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistry;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolver;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolver;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
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

    $services->set('sulu_content.request_workflow_pre_validator.seo_required', SeoRequiredPreValidator::class)
        ->tag('sulu_content.request_workflow_pre_validator')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_content.request_workflow_pre_validator.excerpt_required', ExcerptRequiredPreValidator::class)
        ->tag('sulu_content.request_workflow_pre_validator')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_content.request_workflow_registry', RequestWorkflowRegistry::class)
        ->args([
            '%sulu_content.request_workflows%',
            tagged_locator('sulu_content.request_workflow_validator', indexAttribute: 'key', defaultIndexMethod: 'getKey'),
            tagged_locator('sulu_content.request_workflow_pre_validator', indexAttribute: 'key', defaultIndexMethod: 'getKey'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->alias(RequestWorkflowRegistryInterface::class, 'sulu_content.request_workflow_registry');

    $services->set('sulu_content.request_workflow_resolver', RequestWorkflowResolver::class)
        ->args([
            new Reference('sulu_content.request_workflow_registry'),
            new Reference('sulu_admin.metadata_provider_registry'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->alias(RequestWorkflowResolverInterface::class, 'sulu_content.request_workflow_resolver');

    $services->set('sulu_content.workflow_transition_request_status_resolver', WorkflowTransitionRequestStatusResolver::class)
        ->args([new Reference('sulu_content.request_workflow_registry')])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->alias(WorkflowTransitionRequestStatusResolverInterface::class, 'sulu_content.workflow_transition_request_status_resolver');

    $services->set('sulu_content.request_workflow_pre_validation_subscriber', RequestWorkflowPreValidationSubscriber::class)
        ->args([
            new Reference('sulu_content.request_workflow_resolver'),
            new Reference('security.token_storage'),
        ])
        ->tag('kernel.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_content.workflow_transition_request_cascade_delete_listener', CascadeDeleteWorkflowTransitionRequestListener::class)
        ->tag('doctrine.event_listener', ['event' => 'onFlush']);
};
