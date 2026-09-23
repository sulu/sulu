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

use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestCancelNotAllowedException;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Answers the workflow guards for a person working in the admin. The security contexts it resolves
 * against are registered in the admin context only, so it authorizes nothing outside it.
 *
 * @internal
 */
final class WorkflowTransitionAdminAuthorizer implements WorkflowTransitionAdminAuthorizerInterface
{
    public function __construct(
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $securityContextResolver,
        private readonly SecurityCheckerInterface $securityChecker,
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly WorkflowTransitionRequestStatusResolverInterface $statusResolver,
        private readonly string $suluContext,
    ) {
    }

    public function getPermissions(string $resourceKey, string $resourceId, string $locale): array
    {
        // Outside the admin this class authorizes nothing, so it refuses nothing either.
        if (!$this->isAuthorizedAdminCall()) {
            return ['cancel' => true, 'publish' => true, 'retry' => true, 'review' => true];
        }

        $condition = $this->securityContextResolver->resolve($resourceKey, $resourceId, $locale);
        $edit = $this->securityChecker->hasPermission($condition, PermissionTypes::EDIT);

        return [
            // Withdrawing a request frees the content for editing again, so it takes EDIT.
            'cancel' => $edit,
            'publish' => $this->isPublishGranted($condition, $resourceKey, $resourceId, $locale),
            // Re-running a check is part of fixing the content, not a verdict on it.
            'retry' => $edit,
            'review' => $this->securityChecker->hasPermission($condition, PermissionTypes::REVIEW),
        ];
    }

    public function assertCanPublish(string $resourceKey, string $resourceId, string $locale): void
    {
        if (!$this->isAuthorizedAdminCall()) {
            return;
        }

        $condition = $this->securityContextResolver->resolve($resourceKey, $resourceId, $locale);

        if ($this->isPublishGranted($condition, $resourceKey, $resourceId, $locale)) {
            return;
        }

        throw new AccessDeniedException(\sprintf(
            'Publishing "%s" requires the "%s" permission, or the "%s" permission with an approved request.',
            $condition->getSecurityContext(),
            PermissionTypes::LIVE,
            PermissionTypes::EDIT,
        ));
    }

    public function assertCanReview(string $resourceKey, string $resourceId, string $locale): void
    {
        if (!$this->isAuthorizedAdminCall()) {
            return;
        }

        $condition = $this->securityContextResolver->resolve($resourceKey, $resourceId, $locale);

        // The content API takes `?action=reject` as an ordinary transition, which the security
        // listener maps to EDIT; without this an editor could reject a request they may not review.
        if (!$this->securityChecker->hasPermission($condition, PermissionTypes::REVIEW)) {
            throw new AccessDeniedException(\sprintf(
                'Deciding on a workflow transition request requires the "%s" permission on "%s".',
                PermissionTypes::REVIEW,
                $condition->getSecurityContext(),
            ));
        }
    }

    public function assertCanCancelReview(string $resourceKey, string $resourceId, string $locale): void
    {
        if (!$this->isAuthorizedAdminCall()) {
            return;
        }

        $request = $this->activeWorkflowTransitionRequestProvider->find($resourceKey, $resourceId, $locale);
        if (null === $request) {
            return;
        }

        // Withdrawing a request frees the content for editing again, so it takes the same permission
        // as editing it: the author withdraws their own, a colleague unblocks content left behind.
        if (!$this->securityChecker->hasPermission(
            $this->securityContextResolver->resolve($resourceKey, $resourceId, $locale),
            PermissionTypes::EDIT,
        )) {
            throw new WorkflowTransitionRequestCancelNotAllowedException($request);
        }
    }

    /**
     * An approval delegates the publish right for that one request: EDIT is enough to carry out what
     * the reviewers signed off, but only while the approved request is still the active one.
     */
    private function isPublishGranted(
        SecurityCondition $condition,
        string $resourceKey,
        string $resourceId,
        string $locale,
    ): bool {
        // Asked first and alone, so the live permission answers without looking up the request.
        if ($this->securityChecker->hasPermission($condition, PermissionTypes::LIVE)) {
            return true;
        }

        return $this->securityChecker->hasPermission($condition, PermissionTypes::EDIT)
            && $this->hasApprovedRequest($resourceKey, $resourceId, $locale);
    }

    /**
     * Permissions belong to a person working in the admin. Anything else, a command, a fixture, a
     * consumer, carries no user to check and is let through instead.
     *
     * The context is asked as well as the user, so the class refuses to authorize anywhere its
     * security contexts are not registered rather than answering from half a container.
     */
    private function isAuthorizedAdminCall(): bool
    {
        return SuluKernel::CONTEXT_ADMIN === $this->suluContext
            && $this->tokenStorage->getToken()?->getUser() instanceof UserInterface;
    }

    private function hasApprovedRequest(string $resourceKey, string $resourceId, string $locale): bool
    {
        $request = $this->activeWorkflowTransitionRequestProvider->find($resourceKey, $resourceId, $locale);

        return null !== $request && WorkflowTransitionRequestStatusEnum::APPROVED === $this->statusResolver->resolve($request);
    }
}
