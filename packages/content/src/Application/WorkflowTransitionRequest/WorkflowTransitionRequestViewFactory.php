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

namespace Sulu\Content\Application\WorkflowTransitionRequest;

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Domain\Exception\UnresolvableSecurityContextException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecision;

/**
 * @internal
 */
final class WorkflowTransitionRequestViewFactory implements WorkflowTransitionRequestViewFactoryInterface
{
    public function __construct(
        private readonly RequestWorkflowRegistryInterface $registry,
        private readonly WorkflowTransitionRequestStatusResolverInterface $statusResolver,
        private readonly WorkflowTransitionAdminAuthorizerInterface $authorizer,
        private readonly WorkflowTransitionRequestActionRegistryInterface $actionRegistry,
        private readonly SecurityCheckerInterface $securityChecker,
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $securityContextResolver,
    ) {
    }

    public function build(WorkflowTransitionRequest $request): array
    {
        $workflowName = $request->getWorkflowName();
        $workflow = $this->registry->has($workflowName) ? $this->registry->get($workflowName) : null;
        $requiredKeys = $workflow?->getRequiredValidatorKeys() ?? [];

        return [
            'id' => $request->getId(),
            'resourceKey' => $request->getResourceKey(),
            'resourceId' => $request->getResourceId(),
            'locale' => $request->getLocale(),
            'status' => $this->statusResolver->resolve($request)->value,
            // The admin gates its buttons on these, so the rules live on the server alone.
            'permissions' => $this->authorizer->getPermissions(
                $request->getResourceKey(),
                $request->getResourceId(),
                $request->getLocale(),
            ),
            'actions' => $this->buildActions($request),
            ...$this->buildStatusMessage($request),
            'requestedAt' => $request->getCreated()->format(\DATE_ATOM),
            'createdBy' => $this->serializeUser($request->getCreator()),
            'data' => $request->getData(),
            'approvalProgress' => [
                'required' => $workflow?->getRequiredUserApprovals() ?? 0,
                'approved' => $request->countUserApprovals(),
                'rejected' => $request->countUserRejections(),
            ],
            'checks' => \array_values(\array_map(
                fn (WorkflowTransitionRequestDecision $decision) => [
                    'id' => $decision->getId(),
                    'validatorKey' => $decision->getValidatorKey(),
                    'required' => \in_array($decision->getValidatorKey(), $requiredKeys, true),
                    'status' => $decision->getStatus()->value,
                    'messages' => $this->serializeMessages($decision),
                    'decidedAt' => $decision->getDecidedAt()?->format(\DATE_ATOM),
                ],
                \array_filter($request->getDecisions(), static fn (WorkflowTransitionRequestDecision $decision) => $decision->isValidatorDecision()),
            )),
            'approvals' => \array_values(\array_map(
                fn (WorkflowTransitionRequestDecision $decision) => [
                    'id' => $decision->getId(),
                    'reviewer' => $this->serializeUser($decision->getUser()),
                    'status' => $decision->getStatus()->value,
                    'messages' => $this->serializeMessages($decision),
                    'decidedAt' => $decision->getDecidedAt()?->format(\DATE_ATOM),
                ],
                \array_filter($request->getDecisions(), static fn (WorkflowTransitionRequestDecision $decision) => $decision->isUserDecision()),
            )),
        ];
    }

    /**
     * @return array{statusMessage?: array{message: string, parameters: array<string, string>}}
     */
    private function buildStatusMessage(WorkflowTransitionRequest $request): array
    {
        foreach ($this->actionRegistry->all() as $action) {
            $statusMessage = $action->getStatusMessage($request);
            if (null !== $statusMessage) {
                return ['statusMessage' => $statusMessage];
            }
        }

        return [];
    }

    /**
     * The actions of bundles that fit the request and that the user may take, for the footer of the
     * review overlay. The label travels untranslated like the messages do.
     *
     * @return list<array{key: string, label: string, labelParameters?: array<string, string>}>
     */
    private function buildActions(WorkflowTransitionRequest $request): array
    {
        if (!$request->isOpen()) {
            return [];
        }

        try {
            $condition = $this->securityContextResolver->resolve(
                $request->getResourceKey(),
                $request->getResourceId(),
                $request->getLocale(),
            );
        } catch (UnresolvableSecurityContextException) {
            // The buttons are only a view of the rules, so a broken setup hides them.
            return [];
        }

        $actions = [];
        foreach ($this->actionRegistry->all() as $action) {
            if (!$action->isAvailable($request) || !$this->securityChecker->hasPermission($condition, $action->getPermission())) {
                continue;
            }

            $entry = ['key' => $action::getKey(), 'label' => $action->getLabel()];
            $labelParameters = $action->getLabelParameters($request);
            if ([] !== $labelParameters) {
                $entry['labelParameters'] = $labelParameters;
            }

            $actions[] = $entry;
        }

        return $actions;
    }

    /**
     * Translation keys travel untranslated: only the serializer knows the request's locale.
     *
     * @return list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>
     */
    private function serializeMessages(WorkflowTransitionRequestDecision $decision): array
    {
        return \array_map(
            static fn ($message) => $message->toArray(),
            $decision->getMessages(),
        );
    }

    /**
     * @return array{id: int|string|null, fullName: string}|null
     */
    private function serializeUser(?UserInterface $user): ?array
    {
        if (null === $user) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
        ];
    }
}
