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
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
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
            'requestedAt' => $request->getCreated()->format(\DATE_ATOM),
            'createdBy' => $this->serializeUser($request->getCreator()),
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
