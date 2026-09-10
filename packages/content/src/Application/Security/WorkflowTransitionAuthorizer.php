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

namespace Sulu\Content\Application\Security;

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
final class WorkflowTransitionAuthorizer implements WorkflowTransitionAuthorizerInterface
{
    public function __construct(
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $securityContextResolver,
        private readonly SecurityCheckerInterface $securityChecker,
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly WorkflowTransitionRequestStatusResolverInterface $statusResolver,
    ) {
    }

    public function assertCanPublish(string $resourceKey, string $resourceId, string $locale): void
    {
        if ($this->isSystemCall()) {
            return;
        }

        $condition = $this->securityContextResolver->resolve($resourceKey, $resourceId, $locale);

        if ($this->securityChecker->hasPermission($condition, PermissionTypes::LIVE)) {
            return;
        }

        // An approval delegates the publish right for that one request: EDIT is enough to carry out
        // what the reviewers signed off, but only while the approved request is still the active one.
        if ($this->securityChecker->hasPermission($condition, PermissionTypes::EDIT)
            && $this->hasApprovedRequest($resourceKey, $resourceId, $locale)
        ) {
            return;
        }

        throw new AccessDeniedException(\sprintf(
            'Publishing "%s" requires the "%s" permission, or the "%s" permission with an approved request.',
            $condition->getSecurityContext(),
            PermissionTypes::LIVE,
            PermissionTypes::EDIT,
        ));
    }

    public function assertCanReject(string $resourceKey, string $resourceId, string $locale): void
    {
        if ($this->isSystemCall()) {
            return;
        }

        $condition = $this->securityContextResolver->resolve($resourceKey, $resourceId, $locale);

        // The content API takes `?action=reject` as an ordinary transition, which the security
        // listener maps to EDIT; without this an editor could reject a request they may not review.
        if (!$this->securityChecker->hasPermission($condition, PermissionTypes::REVIEW)) {
            throw new AccessDeniedException(\sprintf(
                'Rejecting a workflow transition request requires the "%s" permission on "%s".',
                PermissionTypes::REVIEW,
                $condition->getSecurityContext(),
            ));
        }
    }

    /**
     * A command, fixture or consumer publishes on the system's behalf, with no user to check.
     */
    private function isSystemCall(): bool
    {
        return !$this->tokenStorage->getToken()?->getUser() instanceof UserInterface;
    }

    private function hasApprovedRequest(string $resourceKey, string $resourceId, string $locale): bool
    {
        $request = $this->activeWorkflowTransitionRequestProvider->find($resourceKey, $resourceId, $locale);

        return null !== $request && WorkflowTransitionRequestStatusEnum::APPROVED === $this->statusResolver->resolve($request);
    }
}
